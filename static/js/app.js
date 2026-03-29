/* Pevesindo POP – app.js */
/* Auto-dismiss alerts after 5 seconds */
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        document.querySelectorAll('.alert.alert-success, .alert.alert-info').forEach(function (el) {
            var alert = bootstrap.Alert.getOrCreateInstance(el);
            alert.close();
        });
    }, 5000);
});
