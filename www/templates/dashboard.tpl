{* Dashboard Template *}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<div id="dashboard-widgets" class="row">
  <div class="col-12 col-md-6 mb-3 dashboard-widget" data-widget="sales">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Sales Today</h5>
        <p class="card-text" id="sales-today">{$sales_today}</p>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-6 mb-3 dashboard-widget" data-widget="open_orders">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Open Orders</h5>
        <p class="card-text" id="open-orders">{$open_orders}</p>
      </div>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="js/dashboard.js"></script>
