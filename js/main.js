document.addEventListener('DOMContentLoaded', function () {
    // Форма обратной связи
    var form = document.getElementById('contactForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var formData = new FormData(this);
            var self = this;

            fetch('/api/contact.php', {
                method: 'POST',
                body: formData
            })
            .then(function (r) { return r.json(); })
            .then(function (result) {
                if (result.success) {
                    alert(result.message || 'Сообщение отправлено');
                    self.reset();
                } else {
                    alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
                }
            })
            .catch(function () {
                alert('Произошла ошибка при отправке');
            });
        });
    }

    // Мобильное меню
    var btn = document.getElementById('mobileMenuBtn');
    var menu = document.getElementById('mobileMenu');
    if (btn && menu) {
        btn.addEventListener('click', function () {
            menu.classList.toggle('active');
            var icon = btn.querySelector('i');
            if (menu.classList.contains('active')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        });
    }
});
