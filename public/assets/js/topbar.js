(function () {
    'use strict';

    var toggle =
        document.getElementById(
            'userSettingsToggle'
        );

    var menu =
        document.getElementById(
            'userSettingsMenu'
        );

    var wrap =
        document.getElementById(
            'userSettings'
        );


    if (
        !toggle ||
        !menu ||
        !wrap
    ) {
        return;
    }


    function setOpen(open) {

        wrap.classList.toggle(
            'is-open',
            open
        );

        toggle.classList.toggle(
            'is-open',
            open
        );

        toggle.setAttribute(
            'aria-expanded',
            open
                ? 'true'
                : 'false'
        );
    }


    toggle.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();

            setOpen(
                !wrap.classList.contains(
                    'is-open'
                )
            );
        }
    );


    document.addEventListener(
        'click',
        function (event) {

            if (
                !wrap.contains(
                    event.target
                )
            ) {
                setOpen(false);
            }

        }
    );


    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
            ) {
                setOpen(false);

                toggle.focus();
            }

        }
    );
    document.addEventListener('DOMContentLoaded', function () {

    const currentPath =
        window.location.pathname.replace(/\/+$/, '') || '/';

    document
        .querySelectorAll('.redirect-url a')
        .forEach(function (link) {

            const linkPath =
                new URL(
                    link.href,
                    window.location.origin
                ).pathname.replace(/\/+$/, '') || '/';

            if (currentPath === linkPath) {
                link.classList.add('is-active');
            }

        });

});

})();