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

})();