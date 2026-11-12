document.addEventListener("DOMContentLoaded", function () {

    const fields = document.querySelectorAll(
        '.reg-page input[type="password"]'
    );

    fields.forEach(function (input) {

        if (input.closest(".salon-password-wrap")) {
            return;
        }

        const wrapper = document.createElement("div");
        wrapper.className = "salon-password-wrap";

        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const toggle = document.createElement("button");

        toggle.type = "button";
        toggle.className = "salon-password-toggle";
        toggle.textContent = "Show";
        toggle.setAttribute("aria-label", "Show password");
        toggle.setAttribute("aria-pressed", "false");

        toggle.addEventListener("click", function () {

            const isHidden = input.type === "password";

            input.type = isHidden ? "text" : "password";

            toggle.textContent = isHidden ? "Hide" : "Show";

            toggle.setAttribute(
                "aria-label",
                isHidden ? "Hide password" : "Show password"
            );

            toggle.setAttribute(
                "aria-pressed",
                String(isHidden)
            );

        });

        wrapper.appendChild(toggle);

    });

});