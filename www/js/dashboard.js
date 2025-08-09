$(function(){
  function loadData(){
    $.getJSON('dashboardwidget.php?action=data', function(data){
      $('#sales-today').text(data.sales_today);
      $('#open-orders').text(data.open_orders);
    });
  }
  loadData();
  setInterval(loadData, 30000);

  $('#dashboard-widgets').sortable({
    handle: '.card-title',
    update: function(){
      var order = [];
      $('.dashboard-widget').each(function(){
        order.push($(this).data('widget'));
      });
      $.post('dashboardwidget.php?action=saveorder', {order: order.join(',')});
    }
  });
});
