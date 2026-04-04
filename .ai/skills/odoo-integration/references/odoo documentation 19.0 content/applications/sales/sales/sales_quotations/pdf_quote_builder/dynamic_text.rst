====================
Dynamic text in PDFs
====================

While creating custom PDFs for quotes, use *dynamic text* for Odoo to auto-fill the PDF content with
information related to the quote from the Odoo database, like names, prices, etc.

Dynamic text values are form components (text inputs) that can be added in a PDF file, and Odoo
automatically fills those values in with information related to the quote.

Dynamic text values
===================

Below are common dynamic text values used in custom PDFs that are already mapped to the correct
fields, and what they represent.

For headers and footers PDF:

- :guilabel:`name`: Sales Order Reference
- :guilabel:`partner_id__name`: Customer Name
- :guilabel:`user_id__name`: Salesperson Name
- :guilabel:`amount_untaxed`: Untaxed Amount
- :guilabel:`amount_total`: Total Amount
- :guilabel:`delivery_date`: Delivery Date
- :guilabel:`validity_date`: Expiration Date
- :guilabel:`client_order_ref`: Customer Reference


For product PDF:

- :guilabel:`description`: Product Description
- :guilabel:`quantity`: Quantity
- :guilabel:`uom`: Unit of Measure (UoM)
- :guilabel:`price_unit`: Price Unit
- :guilabel:`discount`: Discount
- :guilabel:`product_sale_price`: Product List Price
- :guilabel:`taxes`: Taxes name joined by a comma (`,`)
- :guilabel:`tax_excl_price`: Tax Excluded Price
- :guilabel:`tax_incl_price`: Tax Included Price

After uploading a PDF, you can then :guilabel:`Configure dynamic fields`. This will allow you to map
any field name found in your PDF to the field you want to show by writing down any existing path.
Headers and footers starts from the current :guilabel:`sale_order` model, whereas product document
follows their path from their :guilabel:`sale_order_line`. Leaving that path empty allows you to
fill a custom notes, directly from the specific quote that requires it.

.. example::
   When a PDF is built, it's best practice to use common dynamic text values (:guilabel:`name` and
   :guilabel:`partner_id_name`). When uploaded into the database, Odoo auto-populates those fields
   with the information from their respective fields.

   In this case, Odoo would auto-populate the Sales Order Reference in the :guilabel:`name` dynamic
   text field, and the Customer Name in the :guilabel:`partner_id_name` field.

   .. image:: dynamic_text/pdf-quote-builder-sample.png
      :align: center
      :alt: PDF quote being built using common dynamic placeholders.

Once the PDF file(s) are complete, save them to the computer's hard drive, and proceed to upload
them to Odoo via :menuselection:`Sales app --> Configuration --> Headers/Footers`.

.. example::
   When uploading PDF containing the form field :guilabel:`invoice_partner_country`, which is an
   information available in the sales order, configure the :guilabel:`path` of the :guilabel:`Form
   Field Name` to:
   - :guilabel:`partner_invoice_id.country_id.name` for a header or footer document
   - :guilabel:`order_id.partner_invoice_id.country_id.name` for a product document fills the form
   with the invoice partner country's name when the PDF is built.

.. example::
   When uploading any PDF containing the form field :guilabel:`custom_note`, leaving the
   :guilabel:`path` empty allows the seller to write down any note where that form field is in that
   document and shown when the PDF is built.
