// Script global pour l'interface utilisateur de l'agence.
// Ajoute un effet d'apparition de page et gère l'animation des notifications.
document.addEventListener('DOMContentLoaded', function() {
    // Active l'animation de chargement de la page.
    document.body.classList.add('loaded');

    // Ajoute un effet visuel de pression sur les boutons.
    var buttons = document.querySelectorAll('.btn, input[type="submit"], .nav-card');
    buttons.forEach(function(button) {
        button.addEventListener('mousedown', function() {
            button.classList.add('pressed');
        });
        button.addEventListener('mouseup', function() {
            button.classList.remove('pressed');
        });
        button.addEventListener('mouseleave', function() {
            button.classList.remove('pressed');
        });
    });

    document.querySelectorAll('.password-field').forEach(function(wrap) {
        var input = wrap.querySelector('input');
        var btn = wrap.querySelector('.password-toggle');
        if (!input || !btn) {
            return;
        }
        var iconShow = btn.querySelector('.password-toggle-icon--show');
        var iconHide = btn.querySelector('.password-toggle-icon--hide');

        function syncIcons() {
            var visible = input.type === 'text';
            btn.setAttribute('aria-pressed', visible ? 'true' : 'false');
            btn.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            if (iconShow && iconHide) {
                iconShow.hidden = visible;
                iconHide.hidden = !visible;
            }
        }

        btn.addEventListener('click', function() {
            input.type = input.type === 'password' ? 'text' : 'password';
            syncIcons();
        });

        syncIcons();
    });
});
