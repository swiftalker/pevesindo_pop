:show-content:

=============
Point of Sale
=============

Odoo **Point of Sale** is designed for managing shops and restaurants. It is web-browser-based,
allowing it to run on any device, and is built to maintain functionality even during temporary
network outages.

Beyond traditional :doc:`store <point_of_sale/shop>` and :doc:`restaurant
<point_of_sale/restaurant>` settings, Odoo :abbr:`POS (Point of Sale)` also supports a
:doc:`self-ordering <point_of_sale/extra/self_order>` feature, enabling customers to place orders
and make payments using a dedicated kiosk or their own mobile device.

Odoo :abbr:`POS (Point of Sale)` integrates with all essential point-of-sale hardware, including:

- :doc:`Payment terminals <point_of_sale/payment_methods/terminals>`;
- Cash drawers;
- :doc:`Cash machines <point_of_sale/payment_methods/cash_machines>`;
- :doc:`Scales <point_of_sale/hardware_network/scale>`;
- :doc:`Barcode scanners <../inventory_and_mrp/barcode/setup/hardware>`;
- :doc:`Customer displays <point_of_sale/hardware_network/customer_display>`;
- :doc:`Preparation displays <point_of_sale/extra/preparation>`;
- :doc:`Electronic shelf labels <point_of_sale/hardware_network/electronic_labels>`.

This hardware can be connected directly or through an :doc:`IoT system <../general/iot>`.

.. seealso::
   `Odoo Tutorials: Point of Sale tutorials <https://www.odoo.com/slides/point-of-sale-28>`_

.. toctree::
   :titlesonly:

   point_of_sale/use
   point_of_sale/products
   point_of_sale/hardware_network
   point_of_sale/shop
   point_of_sale/restaurant
   point_of_sale/extra
   point_of_sale/payment_methods
   point_of_sale/reporting
