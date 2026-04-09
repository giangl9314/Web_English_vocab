// JavaScript cho trang đăng nhập (Phiên bản "Vừa mới học tới bài Form")
document.addEventListener('DOMContentLoaded', function() {
    
    // Lấy nút ra
    var loginBtn = document.querySelector('.login-button');
    
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            e.preventDefault(); // chặn load lại web
            
            // Lấy value các ô input
            var emailStr = document.getElementById('email').value;
            var passStr = document.getElementById('password').value;
            
            // Validate sơ xài
            if (emailStr == '') {
                alert('Vui lòng nhập Email đi bạn!');
                return;
            }
            if (passStr == '') {
                alert('Thiếu Password rồi!');
                return;
            }
            
            let formData = new FormData();
            formData.append('email', emailStr);
            formData.append('password', passStr);
            
            loginBtn.textContent = 'Đang xử lý...';
            
            fetch('../process/login-process.php', {
                method: 'POST',
                body: formData
            }).then(function(response) {
                if(response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                return response.text();
            }).then(function(textOutput) {
                try {
                    let jsonData = JSON.parse(textOutput);
                    if (jsonData.error_type == 'email_not_found') {
                        let c = confirm(jsonData.message + "\nBạn có muốn qua trang đăng ký không?");
                        if (c) {
                            window.location.href = 'dangki.html';
                        } else {
                            loginBtn.textContent = 'Đăng nhập';
                        }
                    } else if (jsonData.message) {
                        alert(jsonData.message);
                        loginBtn.textContent = 'Đăng nhập';
                    }
                } catch(e) {
                    console.log("Đăng nhập lỗi rùi: " + e);
                    alert("Có lỗi xẩy ra k parse được json");
                    loginBtn.textContent = 'Đăng nhập';
                }
            }).catch(function(e) {
                alert("Lỗi server, bật F12 xem Network");
                loginBtn.textContent = 'Đăng nhập';
            });
        });
    }

    // code social cho có
    var fb = document.querySelector('.facebook-login-button');
    if (fb) {
        fb.addEventListener('click', function(e) { e.preventDefault(); alert("Chưa làm nút này =))"); });
    }
});
