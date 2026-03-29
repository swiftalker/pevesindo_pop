from flask import Flask, render_template, request, redirect, url_for, flash, jsonify
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime, date, timezone
from sqlalchemy import func
import os

app = Flask(__name__)
app.config['SECRET_KEY'] = os.environ.get('SECRET_KEY', 'pevesindo-pop-secret-key')
app.config['SQLALCHEMY_DATABASE_URI'] = os.environ.get('DATABASE_URL', 'sqlite:///pevesindo_pop.db')
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db = SQLAlchemy(app)


# ─── Models ───────────────────────────────────────────────────────────────────

class Produk(db.Model):
    __tablename__ = 'produk'
    id = db.Column(db.Integer, primary_key=True)
    kode = db.Column(db.String(50), unique=True, nullable=False)
    nama = db.Column(db.String(200), nullable=False)
    satuan = db.Column(db.String(50), nullable=False)
    harga_jual = db.Column(db.Float, nullable=False, default=0)
    stok = db.Column(db.Integer, nullable=False, default=0)
    keterangan = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=lambda: datetime.now(timezone.utc))
    detail_orders = db.relationship('DetailOrder', backref='produk', lazy=True)

    def __repr__(self):
        return f'<Produk {self.kode} - {self.nama}>'


class Pelanggan(db.Model):
    __tablename__ = 'pelanggan'
    id = db.Column(db.Integer, primary_key=True)
    kode = db.Column(db.String(50), unique=True, nullable=False)
    nama = db.Column(db.String(200), nullable=False)
    alamat = db.Column(db.Text)
    telepon = db.Column(db.String(50))
    email = db.Column(db.String(200))
    created_at = db.Column(db.DateTime, default=lambda: datetime.now(timezone.utc))
    orders = db.relationship('Order', backref='pelanggan', lazy=True)

    def __repr__(self):
        return f'<Pelanggan {self.kode} - {self.nama}>'


class Order(db.Model):
    __tablename__ = 'order'
    id = db.Column(db.Integer, primary_key=True)
    nomor = db.Column(db.String(50), unique=True, nullable=False)
    tanggal = db.Column(db.Date, nullable=False, default=date.today)
    pelanggan_id = db.Column(db.Integer, db.ForeignKey('pelanggan.id'), nullable=False)
    status = db.Column(db.String(20), nullable=False, default='draft')  # draft, confirmed, selesai, batal
    catatan = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=lambda: datetime.now(timezone.utc))
    detail_orders = db.relationship('DetailOrder', backref='order', lazy=True, cascade='all, delete-orphan')

    @property
    def total(self):
        return sum(d.subtotal for d in self.detail_orders)

    def __repr__(self):
        return f'<Order {self.nomor}>'


class DetailOrder(db.Model):
    __tablename__ = 'detail_order'
    id = db.Column(db.Integer, primary_key=True)
    order_id = db.Column(db.Integer, db.ForeignKey('order.id'), nullable=False)
    produk_id = db.Column(db.Integer, db.ForeignKey('produk.id'), nullable=False)
    qty = db.Column(db.Integer, nullable=False, default=1)
    harga = db.Column(db.Float, nullable=False, default=0)
    diskon = db.Column(db.Float, nullable=False, default=0)

    @property
    def subtotal(self):
        return self.qty * self.harga * (1 - self.diskon / 100)

    def __repr__(self):
        return f'<DetailOrder order={self.order_id} produk={self.produk_id}>'


# ─── Helpers ──────────────────────────────────────────────────────────────────

def format_rupiah(value):
    if value is None:
        return 'Rp 0'
    return 'Rp {:,.0f}'.format(value).replace(',', '.')


app.jinja_env.filters['rupiah'] = format_rupiah


@app.context_processor
def inject_now():
    return {'now': datetime.now(timezone.utc)}


def generate_kode_produk():
    last = Produk.query.order_by(Produk.id.desc()).first()
    num = (last.id + 1) if last else 1
    return f'PRD-{num:04d}'


def generate_kode_pelanggan():
    last = Pelanggan.query.order_by(Pelanggan.id.desc()).first()
    num = (last.id + 1) if last else 1
    return f'PLG-{num:04d}'


def generate_nomor_order():
    today = date.today()
    prefix = f'SO-{today.strftime("%Y%m%d")}'
    count = Order.query.filter(Order.nomor.like(f'{prefix}%')).count()
    return f'{prefix}-{count + 1:03d}'


# ─── Dashboard ────────────────────────────────────────────────────────────────

@app.route('/')
def dashboard():
    total_produk = Produk.query.count()
    total_pelanggan = Pelanggan.query.count()
    total_order = Order.query.count()
    order_confirmed = Order.query.filter_by(status='confirmed').count()
    order_selesai = Order.query.filter_by(status='selesai').count()
    order_batal = Order.query.filter_by(status='batal').count()

    # Total penjualan (order selesai)
    orders_selesai = Order.query.filter_by(status='selesai').all()
    total_penjualan = sum(o.total for o in orders_selesai)

    # Order terbaru
    orders_terbaru = Order.query.order_by(Order.created_at.desc()).limit(5).all()

    return render_template('dashboard.html',
                           total_produk=total_produk,
                           total_pelanggan=total_pelanggan,
                           total_order=total_order,
                           order_confirmed=order_confirmed,
                           order_selesai=order_selesai,
                           order_batal=order_batal,
                           total_penjualan=total_penjualan,
                           orders_terbaru=orders_terbaru)


# ─── Produk ───────────────────────────────────────────────────────────────────

@app.route('/produk')
def produk_list():
    search = request.args.get('search', '')
    query = Produk.query
    if search:
        query = query.filter(
            (Produk.kode.ilike(f'%{search}%')) |
            (Produk.nama.ilike(f'%{search}%'))
        )
    produk_list = query.order_by(Produk.kode).all()
    return render_template('produk/list.html', produk_list=produk_list, search=search)


@app.route('/produk/tambah', methods=['GET', 'POST'])
def produk_tambah():
    if request.method == 'POST':
        kode = request.form.get('kode', '').strip()
        nama = request.form.get('nama', '').strip()
        satuan = request.form.get('satuan', '').strip()
        harga_jual = float(request.form.get('harga_jual', 0) or 0)
        stok = int(request.form.get('stok', 0) or 0)
        keterangan = request.form.get('keterangan', '').strip()

        if not kode or not nama or not satuan:
            flash('Kode, nama, dan satuan wajib diisi.', 'danger')
            return render_template('produk/form.html', action='tambah', data=request.form)

        if Produk.query.filter_by(kode=kode).first():
            flash('Kode produk sudah digunakan.', 'danger')
            return render_template('produk/form.html', action='tambah', data=request.form)

        produk = Produk(kode=kode, nama=nama, satuan=satuan,
                        harga_jual=harga_jual, stok=stok, keterangan=keterangan)
        db.session.add(produk)
        db.session.commit()
        flash('Produk berhasil ditambahkan.', 'success')
        return redirect(url_for('produk_list'))

    kode_auto = generate_kode_produk()
    return render_template('produk/form.html', action='tambah',
                           data={'kode': kode_auto})


@app.route('/produk/<int:id>/edit', methods=['GET', 'POST'])
def produk_edit(id):
    produk = Produk.query.get_or_404(id)
    if request.method == 'POST':
        kode = request.form.get('kode', '').strip()
        nama = request.form.get('nama', '').strip()
        satuan = request.form.get('satuan', '').strip()
        harga_jual = float(request.form.get('harga_jual', 0) or 0)
        stok = int(request.form.get('stok', 0) or 0)
        keterangan = request.form.get('keterangan', '').strip()

        if not kode or not nama or not satuan:
            flash('Kode, nama, dan satuan wajib diisi.', 'danger')
            return render_template('produk/form.html', action='edit', data=request.form, produk=produk)

        existing = Produk.query.filter_by(kode=kode).first()
        if existing and existing.id != id:
            flash('Kode produk sudah digunakan.', 'danger')
            return render_template('produk/form.html', action='edit', data=request.form, produk=produk)

        produk.kode = kode
        produk.nama = nama
        produk.satuan = satuan
        produk.harga_jual = harga_jual
        produk.stok = stok
        produk.keterangan = keterangan
        db.session.commit()
        flash('Produk berhasil diperbarui.', 'success')
        return redirect(url_for('produk_list'))

    return render_template('produk/form.html', action='edit', data=produk, produk=produk)


@app.route('/produk/<int:id>/hapus', methods=['POST'])
def produk_hapus(id):
    produk = Produk.query.get_or_404(id)
    if produk.detail_orders:
        flash('Produk tidak dapat dihapus karena sudah digunakan di order.', 'danger')
        return redirect(url_for('produk_list'))
    db.session.delete(produk)
    db.session.commit()
    flash('Produk berhasil dihapus.', 'success')
    return redirect(url_for('produk_list'))


# ─── Pelanggan ────────────────────────────────────────────────────────────────

@app.route('/pelanggan')
def pelanggan_list():
    search = request.args.get('search', '')
    query = Pelanggan.query
    if search:
        query = query.filter(
            (Pelanggan.kode.ilike(f'%{search}%')) |
            (Pelanggan.nama.ilike(f'%{search}%'))
        )
    pelanggan_list = query.order_by(Pelanggan.kode).all()
    return render_template('pelanggan/list.html', pelanggan_list=pelanggan_list, search=search)


@app.route('/pelanggan/tambah', methods=['GET', 'POST'])
def pelanggan_tambah():
    if request.method == 'POST':
        kode = request.form.get('kode', '').strip()
        nama = request.form.get('nama', '').strip()
        alamat = request.form.get('alamat', '').strip()
        telepon = request.form.get('telepon', '').strip()
        email = request.form.get('email', '').strip()

        if not kode or not nama:
            flash('Kode dan nama pelanggan wajib diisi.', 'danger')
            return render_template('pelanggan/form.html', action='tambah', data=request.form)

        if Pelanggan.query.filter_by(kode=kode).first():
            flash('Kode pelanggan sudah digunakan.', 'danger')
            return render_template('pelanggan/form.html', action='tambah', data=request.form)

        pelanggan = Pelanggan(kode=kode, nama=nama, alamat=alamat,
                              telepon=telepon, email=email)
        db.session.add(pelanggan)
        db.session.commit()
        flash('Pelanggan berhasil ditambahkan.', 'success')
        return redirect(url_for('pelanggan_list'))

    kode_auto = generate_kode_pelanggan()
    return render_template('pelanggan/form.html', action='tambah',
                           data={'kode': kode_auto})


@app.route('/pelanggan/<int:id>/edit', methods=['GET', 'POST'])
def pelanggan_edit(id):
    pelanggan = Pelanggan.query.get_or_404(id)
    if request.method == 'POST':
        kode = request.form.get('kode', '').strip()
        nama = request.form.get('nama', '').strip()
        alamat = request.form.get('alamat', '').strip()
        telepon = request.form.get('telepon', '').strip()
        email = request.form.get('email', '').strip()

        if not kode or not nama:
            flash('Kode dan nama pelanggan wajib diisi.', 'danger')
            return render_template('pelanggan/form.html', action='edit', data=request.form, pelanggan=pelanggan)

        existing = Pelanggan.query.filter_by(kode=kode).first()
        if existing and existing.id != id:
            flash('Kode pelanggan sudah digunakan.', 'danger')
            return render_template('pelanggan/form.html', action='edit', data=request.form, pelanggan=pelanggan)

        pelanggan.kode = kode
        pelanggan.nama = nama
        pelanggan.alamat = alamat
        pelanggan.telepon = telepon
        pelanggan.email = email
        db.session.commit()
        flash('Pelanggan berhasil diperbarui.', 'success')
        return redirect(url_for('pelanggan_list'))

    return render_template('pelanggan/form.html', action='edit', data=pelanggan, pelanggan=pelanggan)


@app.route('/pelanggan/<int:id>/hapus', methods=['POST'])
def pelanggan_hapus(id):
    pelanggan = Pelanggan.query.get_or_404(id)
    if pelanggan.orders:
        flash('Pelanggan tidak dapat dihapus karena sudah memiliki order.', 'danger')
        return redirect(url_for('pelanggan_list'))
    db.session.delete(pelanggan)
    db.session.commit()
    flash('Pelanggan berhasil dihapus.', 'success')
    return redirect(url_for('pelanggan_list'))


# ─── Order ────────────────────────────────────────────────────────────────────

@app.route('/order')
def order_list():
    search = request.args.get('search', '')
    status_filter = request.args.get('status', '')
    query = Order.query
    if search:
        query = query.join(Pelanggan).filter(
            (Order.nomor.ilike(f'%{search}%')) |
            (Pelanggan.nama.ilike(f'%{search}%'))
        )
    if status_filter:
        query = query.filter_by(status=status_filter)
    orders = query.order_by(Order.tanggal.desc(), Order.id.desc()).all()
    return render_template('order/list.html', orders=orders,
                           search=search, status_filter=status_filter)


@app.route('/order/tambah', methods=['GET', 'POST'])
def order_tambah():
    pelanggan_list = Pelanggan.query.order_by(Pelanggan.nama).all()
    produk_list = Produk.query.order_by(Produk.nama).all()

    if request.method == 'POST':
        nomor = request.form.get('nomor', '').strip()
        tanggal_str = request.form.get('tanggal', '').strip()
        pelanggan_id = request.form.get('pelanggan_id', '')
        catatan = request.form.get('catatan', '').strip()

        produk_ids = request.form.getlist('produk_id[]')
        qtys = request.form.getlist('qty[]')
        hargas = request.form.getlist('harga[]')
        diskons = request.form.getlist('diskon[]')

        errors = []
        if not nomor:
            errors.append('Nomor order wajib diisi.')
        if not tanggal_str:
            errors.append('Tanggal wajib diisi.')
        if not pelanggan_id:
            errors.append('Pelanggan wajib dipilih.')
        if not produk_ids:
            errors.append('Minimal satu produk harus ditambahkan.')
        if Order.query.filter_by(nomor=nomor).first():
            errors.append('Nomor order sudah digunakan.')

        if errors:
            for e in errors:
                flash(e, 'danger')
            return render_template('order/form.html', action='tambah',
                                   pelanggan_list=pelanggan_list,
                                   produk_list=produk_list,
                                   data=request.form,
                                   tanggal_iso=tanggal_str)

        tanggal = datetime.strptime(tanggal_str, '%Y-%m-%d').date()
        order = Order(nomor=nomor, tanggal=tanggal,
                      pelanggan_id=int(pelanggan_id), catatan=catatan)
        db.session.add(order)
        db.session.flush()

        for i, pid in enumerate(produk_ids):
            if not pid:
                continue
            detail = DetailOrder(
                order_id=order.id,
                produk_id=int(pid),
                qty=int(qtys[i] or 1),
                harga=float(hargas[i] or 0),
                diskon=float(diskons[i] or 0)
            )
            db.session.add(detail)

        db.session.commit()
        flash('Order berhasil dibuat.', 'success')
        return redirect(url_for('order_detail', id=order.id))

    nomor_auto = generate_nomor_order()
    return render_template('order/form.html', action='tambah',
                           pelanggan_list=pelanggan_list,
                           produk_list=produk_list,
                           data={'nomor': nomor_auto,
                                 'tanggal': date.today().isoformat()},
                           tanggal_iso=date.today().isoformat())


@app.route('/order/<int:id>')
def order_detail(id):
    order = Order.query.get_or_404(id)
    return render_template('order/detail.html', order=order)


@app.route('/order/<int:id>/edit', methods=['GET', 'POST'])
def order_edit(id):
    order = Order.query.get_or_404(id)
    pelanggan_list = Pelanggan.query.order_by(Pelanggan.nama).all()
    produk_list = Produk.query.order_by(Produk.nama).all()

    if order.status in ('selesai', 'batal'):
        flash('Order yang sudah selesai atau dibatalkan tidak dapat diedit.', 'danger')
        return redirect(url_for('order_detail', id=id))

    if request.method == 'POST':
        nomor = request.form.get('nomor', '').strip()
        tanggal_str = request.form.get('tanggal', '').strip()
        pelanggan_id = request.form.get('pelanggan_id', '')
        catatan = request.form.get('catatan', '').strip()

        produk_ids = request.form.getlist('produk_id[]')
        qtys = request.form.getlist('qty[]')
        hargas = request.form.getlist('harga[]')
        diskons = request.form.getlist('diskon[]')

        errors = []
        if not nomor:
            errors.append('Nomor order wajib diisi.')
        if not tanggal_str:
            errors.append('Tanggal wajib diisi.')
        if not pelanggan_id:
            errors.append('Pelanggan wajib dipilih.')
        if not produk_ids:
            errors.append('Minimal satu produk harus ditambahkan.')

        existing = Order.query.filter_by(nomor=nomor).first()
        if existing and existing.id != id:
            errors.append('Nomor order sudah digunakan.')

        if errors:
            for e in errors:
                flash(e, 'danger')
            return render_template('order/form.html', action='edit',
                                   pelanggan_list=pelanggan_list,
                                   produk_list=produk_list,
                                   data=request.form, order=order,
                                   tanggal_iso=tanggal_str)

        tanggal = datetime.strptime(tanggal_str, '%Y-%m-%d').date()
        order.nomor = nomor
        order.tanggal = tanggal
        order.pelanggan_id = int(pelanggan_id)
        order.catatan = catatan

        # Replace details
        for detail in order.detail_orders:
            db.session.delete(detail)
        db.session.flush()

        for i, pid in enumerate(produk_ids):
            if not pid:
                continue
            detail = DetailOrder(
                order_id=order.id,
                produk_id=int(pid),
                qty=int(qtys[i] or 1),
                harga=float(hargas[i] or 0),
                diskon=float(diskons[i] or 0)
            )
            db.session.add(detail)

        db.session.commit()
        flash('Order berhasil diperbarui.', 'success')
        return redirect(url_for('order_detail', id=order.id))

    return render_template('order/form.html', action='edit',
                           pelanggan_list=pelanggan_list,
                           produk_list=produk_list,
                           data=order, order=order,
                           tanggal_iso=order.tanggal.isoformat())


@app.route('/order/<int:id>/status', methods=['POST'])
def order_update_status(id):
    order = Order.query.get_or_404(id)
    new_status = request.form.get('status', '')
    valid_statuses = ['draft', 'confirmed', 'selesai', 'batal']
    if new_status in valid_statuses:
        order.status = new_status
        db.session.commit()
        flash(f'Status order berhasil diubah ke "{new_status}".', 'success')
    else:
        flash('Status tidak valid.', 'danger')
    return redirect(url_for('order_detail', id=id))


@app.route('/order/<int:id>/hapus', methods=['POST'])
def order_hapus(id):
    order = Order.query.get_or_404(id)
    if order.status == 'selesai':
        flash('Order yang sudah selesai tidak dapat dihapus.', 'danger')
        return redirect(url_for('order_list'))
    db.session.delete(order)
    db.session.commit()
    flash('Order berhasil dihapus.', 'success')
    return redirect(url_for('order_list'))


# ─── Laporan ──────────────────────────────────────────────────────────────────

@app.route('/laporan')
def laporan():
    bulan = request.args.get('bulan', date.today().strftime('%Y-%m'))
    try:
        tahun, bln = int(bulan.split('-')[0]), int(bulan.split('-')[1])
    except (ValueError, IndexError):
        tahun, bln = date.today().year, date.today().month

    orders = (Order.query
              .filter(Order.status == 'selesai')
              .filter(db.extract('year', Order.tanggal) == tahun)
              .filter(db.extract('month', Order.tanggal) == bln)
              .order_by(Order.tanggal)
              .all())

    total = sum(o.total for o in orders)

    # Per-produk summary
    produk_summary = {}
    for o in orders:
        for d in o.detail_orders:
            if d.produk_id not in produk_summary:
                produk_summary[d.produk_id] = {
                    'nama': d.produk.nama,
                    'qty': 0,
                    'total': 0
                }
            produk_summary[d.produk_id]['qty'] += d.qty
            produk_summary[d.produk_id]['total'] += d.subtotal

    return render_template('laporan.html',
                           orders=orders,
                           total=total,
                           bulan=bulan,
                           produk_summary=produk_summary)


# ─── API ──────────────────────────────────────────────────────────────────────

@app.route('/api/produk/<int:id>')
def api_produk(id):
    produk = Produk.query.get_or_404(id)
    return jsonify({'id': produk.id, 'nama': produk.nama,
                    'harga_jual': produk.harga_jual, 'satuan': produk.satuan})


# ─── Main ─────────────────────────────────────────────────────────────────────

def seed_data():
    if Produk.query.count() == 0:
        products = [
            Produk(kode='PRD-0001', nama='Beras Premium 5kg', satuan='Karung',
                   harga_jual=75000, stok=100),
            Produk(kode='PRD-0002', nama='Minyak Goreng 1L', satuan='Botol',
                   harga_jual=18000, stok=200),
            Produk(kode='PRD-0003', nama='Gula Pasir 1kg', satuan='Kg',
                   harga_jual=14000, stok=150),
        ]
        db.session.bulk_save_objects(products)

    if Pelanggan.query.count() == 0:
        customers = [
            Pelanggan(kode='PLG-0001', nama='Toko Maju Jaya',
                      alamat='Jl. Raya No. 1, Jakarta', telepon='021-1234567'),
            Pelanggan(kode='PLG-0002', nama='CV. Sejahtera',
                      alamat='Jl. Mawar No. 5, Bandung', telepon='022-7654321'),
        ]
        db.session.bulk_save_objects(customers)
    db.session.commit()


if __name__ == '__main__':
    with app.app_context():
        db.create_all()
        seed_data()
    debug = os.environ.get('FLASK_DEBUG', '0') == '1'
    app.run(debug=debug, host='0.0.0.0', port=5000)
