<?php
// index.php - UI
session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Calculator — calculator</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body>
  <main class="wrap">
    <div class="calculator card" role="application" aria-label="Calculator">
      <header class="top">
        <div class="title">Calculator</div>
        <div class="subtitle">PHP • jQuery • AJAX</div>
      </header>

      <section class="display">
        <div id="historyLine" class="history-line"> </div>
        <input id="expr" class="expr" type="text" inputmode="numeric" autocomplete="off" spellcheck="false" aria-label="Expression" />
        <div id="result" class="result">0</div>
      </section>

      <section class="controls">
        <div class="row">
          <button class="btn small" data-action="memory" data-op="MC">MC</button>
          <button class="btn small" data-action="memory" data-op="MR">MR</button>
          <button class="btn small" data-action="memory" data-op="M+">M+</button>
          <button class="btn small" data-action="memory" data-op="M-">M-</button>
          <button class="btn small danger" data-action="clear">C</button>
        </div>

        <div class="row">
          <button class="btn op" data-val="(">(</button>
          <button class="btn op" data-val=")">)</button>
          <button class="btn op" data-val="^">^</button>
          <button class="btn op" data-val="√">√</button>
          <button class="btn op" data-val="%">%</button>
        </div>

        <!-- digits + basic ops -->
        <div class="row">
          <button class="btn digit" data-val="7">7</button>
          <button class="btn digit" data-val="8">8</button>
          <button class="btn digit" data-val="9">9</button>
          <button class="btn op" data-val="/">÷</button>
          <button class="btn special" id="del">DEL</button>
        </div>

        <div class="row">
          <button class="btn digit" data-val="4">4</button>
          <button class="btn digit" data-val="5">5</button>
          <button class="btn digit" data-val="6">6</button>
          <button class="btn op" data-val="*">×</button>
          <button class="btn special" id="ans">Ans</button>
        </div>

        <div class="row">
          <button class="btn digit" data-val="1">1</button>
          <button class="btn digit" data-val="2">2</button>
          <button class="btn digit" data-val="3">3</button>
          <button class="btn op" data-val="-">−</button>
          <button class="btn equals tall" id="equals">=</button>
        </div>

        <div class="row">
          <button class="btn zero digit" data-val="0">0</button>
          <button class="btn digit" data-val=".">.</button>
          <button class="btn op" data-val="+">+</button>
        </div>
      </section>

    <!-- Show History Button -->
<button id="showHistory" class="btn small">Show History</button>

<!-- History Modal -->
<div id="historyModal" class="modal">
  <div class="modal-content">
    <span id="closeHistory" class="close">&times;</span>
    <h2>Calculation History</h2>
    <button class="btn small" id="clearHistory">Clear</button>
    <ul id="historyModalList"></ul>
  </div>
</div>

    </div>
  </main>

  <script src="assets/js/app.js"></script>
</body>
</html>
