$(function(){
  const $expr = $('#expr');
  const $result = $('#result');
  const $historyList = $('#historyList');
  const $historyLine = $('#historyLine');
  const $historyModal = $('#historyModal');
  const $historyModalList = $('#historyModalList');

  // helper to send AJAX
  function ajaxPost(data, cb){
    $.ajax({
      url: 'api.php',
      method: 'POST',
      data: data,
      dataType: 'json'
    }).done(cb).fail(function(){ cb({success:false,error:'Network error'}); });
  }

  // render history in modal
  function renderHistory(arr){
    $historyModalList.empty();
    arr = arr || [];
    arr.forEach(item=>{
      const li = $('<li>');
      li.append($('<span>').addClass('expr').text(item.expr));
      li.append($('<span>').addClass('res').text(item.result));
      li.on('click', function(){
        $expr.val(item.expr); 
        $expr.trigger('input');
        $expr.focus();
        closeHistory();
      });
      $historyModalList.append(li);
    });
    $historyLine.text(arr.length ? (arr[0].expr + ' = ' + arr[0].result) : '');
  }

  // request history initially
  ajaxPost({action:'history', do:'get'}, function(res){
    if (res.success) renderHistory(res.history);
  });

  function appendVal(v){
    const cur = $expr.val();
    $expr.val(cur + v);
    $expr.trigger('input');
  }

  $(document).on('keydown', function(e){
    const key = e.key;
    if (/^[0-9\+\-\*\/\^\%\(\)\.]$/.test(key)) {
      appendVal(key);
      e.preventDefault();
    } else if (key === 'Enter') {
      $('#equals').trigger('click'); e.preventDefault();
    } else if (key === 'Backspace') {
      $('#del').trigger('click'); e.preventDefault();
    } else if (key === 'Escape') {
      $('[data-action="clear"]').trigger('click'); e.preventDefault();
    }
  });

  $('.digit').on('click touchend', function(){
    appendVal($(this).data('val'));
    $expr.focus();
  });
  $('.op').on('click touchend', function(){
    let v = $(this).data('val');
    appendVal(v);
    $expr.focus();
  });

  $('#del').on('click touchend', function(){
    const cur = $expr.val();
    $expr.val(cur.slice(0,-1));
    $expr.trigger('input');
  });

  $('[data-action="clear"]').on('click touchend', function(){
    $expr.val('');
    $result.text('0');
    $expr.focus();
  });

  $('#equals').on('click touchend', function(){
    const expr = $expr.val().trim();
    if (!expr) return;
    if (expr.length > 200) { alert('Expression too long'); return; }
    ajaxPost({action:'compute', expr: expr}, function(res){
      if (!res.success) {
        alert('Error: ' + (res.error||'unknown'));
        $result.text('Error');
      } else {
        $result.text(res.result);
        renderHistory(res.history);
      }
    });
  });

  $('#ans').on('click touchend', function(){
    const top = $historyModalList.children().first();
    if (top.length) {
      const value = top.find('.res').text();
      appendVal(value);
    }
    $expr.focus();
  });

  $('[data-action="memory"]').on('click touchend', function(){
    const op = $(this).data('op');
    if (op === 'MR') {
      ajaxPost({action:'memory', op:'MR'}, function(res){
        if (res.success) appendVal(String(res.memory));
      });
    } else if (op === 'MC') {
      ajaxPost({action:'memory', op:'MC'}, function(res){
        if (res.success) alert('Memory cleared');
      });
    } else if (op === 'M+' || op === 'M-') {
      let val = parseFloat($result.text());
      if (isNaN(val)) val = parseFloat($expr.val()) || 0;
      ajaxPost({action:'memory', op:op, val:val}, function(res){
        if (res.success) alert('Memory updated: ' + res.memory);
      });
    }
  });

  $('#clearHistory').on('click touchend', function(){
    if (!confirm('Clear history?')) return;
    ajaxPost({action:'history', do:'clear'}, function(res){
      if (res.success) renderHistory([]);
    });
  });

  $expr.on('input', function(){
    const s = $(this).val();
    const cleaned = s.replace(/[^\d\.\+\-\*\/\^\%\(\)sqrta-zA-Z√ ]/g,'');
    if (cleaned !== s) $(this).val(cleaned);
    $historyLine.text(cleaned);
  });

  $('button').on('touchstart', function(){ $(this).addClass('touch'); });
  $('button').on('touchend', function(){ $(this).removeClass('touch'); });

  // Popup show/hide
  $('#showHistory').on('click', function(){
    $historyModal.show();
  });
  $('#closeHistory').on('click', function(){
    closeHistory();
  });
  function closeHistory(){
    $historyModal.hide();
  }
});
