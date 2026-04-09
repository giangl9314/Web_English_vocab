// forgot-password-modal.js (Simplified Version)
document.addEventListener('DOMContentLoaded', function() {
    var forgotLink = document.querySelector('.forgot-password-link');
    if(forgotLink) {
        forgotLink.addEventListener('click', function(e) {
            e.preventDefault();
            var email = prompt("Chức năng quên mật khẩu tạm thời. Hãy nhập email của bạn vào:");
            if(email) {
                alert("Đã gửi yêu cầu về hệ thống cho email " + email + ", nhưng mà chức năng đang develop =))");
            }
        });
    }
});
