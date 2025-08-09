(function() {
  function initKanban() {
    var stages = document.querySelectorAll('#sales-funnel-board .kanban-stage');
    stages.forEach(function(stage) {
      stage.addEventListener('dragover', function(e) {
        e.preventDefault();
      });
      stage.addEventListener('drop', function(e) {
        var id = e.dataTransfer.getData('text/plain');
        var item = document.querySelector('.kanban-item[data-id="' + id + '"]');
        e.target.querySelector('.kanban-items').appendChild(item);
      });
    });
    var items = document.querySelectorAll('.kanban-item');
    items.forEach(function(item) {
      item.setAttribute('draggable', true);
      item.addEventListener('dragstart', function(e) {
        e.dataTransfer.setData('text/plain', this.getAttribute('data-id'));
      });
    });
  }
  document.addEventListener('DOMContentLoaded', initKanban);
})();
