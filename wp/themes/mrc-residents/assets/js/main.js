/* =====================================================================
   ◯◯マンション 大規模修繕 居住者専用サイト
   UI挙動制御スクリプト  main.js
   - Vanilla JSのみ（外部ライブラリ不使用）
   - 各機能は data-* 属性で対象を検出し、要素が無ければ何もしない
   ===================================================================== */
(function () {
  'use strict';

  /* -----------------------------------------------------------------
     1. SPナビゲーション（ハンバーガー）開閉
  ----------------------------------------------------------------- */
  function initNavToggle() {
    var toggle = document.querySelector('[data-nav-toggle]');
    var nav = document.querySelector('[data-global-nav]');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });

    // ナビ内リンク押下で閉じる（SP）
    nav.addEventListener('click', function (e) {
      if (e.target.closest('a')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* -----------------------------------------------------------------
     2. アコーディオン（はじめての方へ / Q&A）
        - <div class="accordion"> 内の [data-accordion-trigger] を制御
        - 高さアニメーションのため scrollHeight を利用
  ----------------------------------------------------------------- */
  function initAccordions() {
    var triggers = document.querySelectorAll('[data-accordion-trigger]');
    Array.prototype.forEach.call(triggers, function (trigger) {
      var panel = document.getElementById(trigger.getAttribute('aria-controls'));
      if (!panel) return;

      // 初期状態（aria-expanded="true" なら開いておく）
      if (trigger.getAttribute('aria-expanded') === 'true') {
        panel.style.maxHeight = panel.scrollHeight + 'px';
      }

      trigger.addEventListener('click', function () {
        var expanded = trigger.getAttribute('aria-expanded') === 'true';
        trigger.setAttribute('aria-expanded', String(!expanded));
        if (expanded) {
          panel.style.maxHeight = '0px';
        } else {
          panel.style.maxHeight = panel.scrollHeight + 'px';
        }
      });
    });

    // ウィンドウリサイズ時、開いているパネルの高さを追従
    window.addEventListener('resize', function () {
      var open = document.querySelectorAll('[data-accordion-trigger][aria-expanded="true"]');
      Array.prototype.forEach.call(open, function (trigger) {
        var panel = document.getElementById(trigger.getAttribute('aria-controls'));
        if (panel) panel.style.maxHeight = panel.scrollHeight + 'px';
      });
    });
  }

  /* -----------------------------------------------------------------
     3. タブ / フィルタチップ切替（新着掲示板・一覧の絞り込み）
        - [data-filter-group] 内の [data-filter] で対象カテゴリを切替
        - [data-filter-item] の data-category と照合して表示/非表示
  ----------------------------------------------------------------- */
  function initFilters() {
    var groups = document.querySelectorAll('[data-filter-group]');
    Array.prototype.forEach.call(groups, function (group) {
      var chips = group.querySelectorAll('[data-filter]');
      var targetSelector = group.getAttribute('data-filter-target');
      var items = targetSelector
        ? document.querySelectorAll(targetSelector + ' [data-filter-item]')
        : [];

      group.addEventListener('click', function (e) {
        var chip = e.target.closest('[data-filter]');
        if (!chip) return;

        Array.prototype.forEach.call(chips, function (c) {
          c.classList.toggle('is-active', c === chip);
          c.setAttribute('aria-pressed', String(c === chip));
        });

        var cat = chip.getAttribute('data-filter');
        Array.prototype.forEach.call(items, function (item) {
          var show = cat === 'all' || item.getAttribute('data-category') === cat;
          item.classList.toggle('is-hidden', !show);
        });
      });
    });
  }

  /* -----------------------------------------------------------------
     4. 多段フォーム（入力 → 確認 → 完了）
        - [data-form-flow] を親に、[data-step="input|confirm|done"] を切替
        - 「確認する」で入力値を確認画面へ反映しステップ移動
        - ここではモックのため実送信は行わない（preventDefault）
  ----------------------------------------------------------------- */
  function initFormFlow() {
    var flows = document.querySelectorAll('[data-form-flow]');
    Array.prototype.forEach.call(flows, function (flow) {
      var form = flow.querySelector('form');
      var steps = {
        input: flow.querySelector('[data-step="input"]'),
        confirm: flow.querySelector('[data-step="confirm"]'),
        done: flow.querySelector('[data-step="done"]')
      };
      var stepbarItems = flow.querySelectorAll('[data-stepbar] li');

      function setStep(name) {
        Object.keys(steps).forEach(function (key) {
          if (steps[key]) steps[key].classList.toggle('is-hidden', key !== name);
        });
        // ステップバー状態
        var order = ['input', 'confirm', 'done'];
        var currentIndex = order.indexOf(name);
        Array.prototype.forEach.call(stepbarItems, function (li, i) {
          li.classList.toggle('is-active', i === currentIndex);
          li.classList.toggle('is-done', i < currentIndex);
        });
        window.scrollTo({ top: flow.offsetTop - 80, behavior: 'smooth' });
      }

      // 確認画面へ（バリデーション → 値反映）
      if (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          if (!form.checkValidity()) {
            form.reportValidity();
            return;
          }
          // 確認画面へ値を差し込む
          var reviewFields = flow.querySelectorAll('[data-review]');
          Array.prototype.forEach.call(reviewFields, function (out) {
            var key = out.getAttribute('data-review');
            var field = form.elements[key];
            var value = '';
            if (field) {
              if (field.length && field[0] && field[0].type === 'radio') {
                // ラジオグループ
                for (var i = 0; i < field.length; i++) {
                  if (field[i].checked) {
                    value = field[i].getAttribute('data-label') || field[i].value;
                  }
                }
              } else if (field.type === 'radio') {
                value = field.checked ? (field.getAttribute('data-label') || field.value) : '';
              } else {
                value = field.value;
              }
            }
            out.textContent = value.trim() || '（未入力）';
          });
          setStep('confirm');
        });
      }

      // 「修正する」で入力へ戻る
      var backBtns = flow.querySelectorAll('[data-flow-back]');
      Array.prototype.forEach.call(backBtns, function (btn) {
        btn.addEventListener('click', function () { setStep('input'); });
      });

      // 「送信する」で完了へ
      var submitBtns = flow.querySelectorAll('[data-flow-submit]');
      Array.prototype.forEach.call(submitBtns, function (btn) {
        btn.addEventListener('click', function () {
          // 実装時はここでAjax送信。モックでは完了画面へ遷移。
          setStep('done');
        });
      });
    });
  }

  /* -----------------------------------------------------------------
     5. 管理画面トグルのラベル連動（ON/OFF表示）
  ----------------------------------------------------------------- */
  function initToggles() {
    var toggles = document.querySelectorAll('[data-toggle-switch]');
    Array.prototype.forEach.call(toggles, function (input) {
      var label = input.closest('.toggle') && input.closest('.toggle').parentNode.querySelector('.toggle-state');
      function sync() { if (label) label.textContent = input.checked ? 'ON' : 'OFF'; }
      sync();
      input.addEventListener('change', sync);
    });
  }

  /* -----------------------------------------------------------------
     6. 動画プレイヤー（サムネイル → クリックで埋め込み再生）
  ----------------------------------------------------------------- */
  function initVideoPlayers() {
    var facades = document.querySelectorAll('[data-video-facade]');
    Array.prototype.forEach.call(facades, function (facade) {
      facade.addEventListener('click', function () {
        var player = facade.closest('[data-video-player]');
        if (!player) return;
        var frame = player.querySelector('[data-video-frame]');
        var iframe = frame ? frame.querySelector('iframe') : null;
        // 自動再生パラメータを付与（クリックというユーザー操作起点なので許可される）
        if (iframe) {
          var src = iframe.getAttribute('src');
          if (src && src.indexOf('autoplay=1') === -1) {
            iframe.setAttribute('src', src + (src.indexOf('?') > -1 ? '&' : '?') + 'autoplay=1');
          }
        }
        if (frame) frame.classList.remove('is-hidden');
        facade.style.display = 'none';
      });
    });
  }

  /* -----------------------------------------------------------------
     7. ログイン前トップ：メインビジュアルの表示位置をレスポンシブ切替
        - PC   ：ヒーロー内（ログインの左）に配置＝現状のまま
        - モバイル：ログインを最上部に残したまま、写真をサイト説明
                   （RESIDENTS PORTAL＝#about）の背景へ移し、キャッチコピーを
                   写真に重ねて見せる
        - DOMノードを移すだけ（背景化はCSS側で制御）
  ----------------------------------------------------------------- */
  function initHeroVisualReflow() {
    var visual = document.querySelector('.hero .hero__visual');
    var about = document.getElementById('about');
    if (!visual || !about) return;

    var homeParent = visual.parentNode;   // 復帰先＝ヒーロー内
    var homeAnchor = visual.nextSibling;  // 復帰時の挿入位置（ログインの前）
    // ヒーローが縦積みになる幅（＝写真がログインの上に来る幅）以下で適用
    var mq = window.matchMedia('(max-width: 860px)');

    function apply() {
      if (mq.matches) {
        // モバイル：#about の先頭（＝背景レイヤー）へ
        if (visual.parentNode !== about) {
          about.insertBefore(visual, about.firstChild);
        }
      } else if (visual.parentNode !== homeParent) {
        // PC：ヒーロー内の元位置へ戻す
        homeParent.insertBefore(visual, homeAnchor);
      }
    }

    apply();
    if (mq.addEventListener) {
      mq.addEventListener('change', apply);
    } else if (mq.addListener) {
      mq.addListener(apply);
    }
  }

  /* -----------------------------------------------------------------
     初期化
  ----------------------------------------------------------------- */
  document.addEventListener('DOMContentLoaded', function () {
    initNavToggle();
    initAccordions();
    initFilters();
    initFormFlow();
    initToggles();
    initVideoPlayers();
    initHeroVisualReflow();
  });
})();
