// confirm dialogs for forms marked with data-confirm
document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
});

// show / hide password in the record form
var pwToggle = document.getElementById('pwToggle');
if (pwToggle) {
    pwToggle.addEventListener('click', function () {
        var input = document.getElementById('pwInput');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        pwToggle.textContent = show ? 'Hide' : 'Show';
    });
}

// record form: the layout changes with the chosen game
(function () {
    var form = document.getElementById('recordForm');
    if (!form || !window.GAME_META) return;

    var select = document.getElementById('gameSelect');
    var fixed  = document.getElementById('gameName');   // edit mode
    var levelLabel = document.getElementById('levelLabel');
    var levelInput = document.getElementById('levelInput');
    var itemsLabel = document.getElementById('itemsLabel');

    function currentGame() {
        if (fixed) return fixed.value;
        var opt = select.options[select.selectedIndex];
        return opt ? opt.getAttribute('data-name') : '';
    }

    function apply() {
        var meta = window.GAME_META[currentGame()];
        form.querySelectorAll('[data-group]').forEach(function (el) {
            var on = meta && el.getAttribute('data-group') === meta.group;
            el.style.display = on ? '' : 'none';
            el.querySelectorAll('input, select').forEach(function (i) { i.disabled = !on; });
        });
        if (meta) {
            levelLabel.textContent = meta.level_label + ' (1-' + meta.level_max + ')';
            levelInput.max = meta.level_max;
            itemsLabel.textContent = meta.items_label;
        } else {
            levelLabel.textContent = 'Level';
            levelInput.max = 300;
            itemsLabel.textContent = 'Featured items';
        }
    }

    if (select) select.addEventListener('change', apply);
    apply();
})();
