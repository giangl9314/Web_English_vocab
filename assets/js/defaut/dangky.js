// JavaScript cho trang đăng ký 
document.addEventListener('DOMContentLoaded', function() {
    var regBtn = document.querySelector('.login-button');

    if (regBtn) {
        regBtn.addEventListener('click', function(e) {
            e.preventDefault();

            var nameStr = document.getElementById('name').value;
            var emailStr = document.getElementById('email').value;
            var passStr = document.getElementById('password').value;
            var confirmPassStr = document.getElementById('confirm-password').value;
            var termChk = document.getElementById('terms-checkbox');

            // Tính năng validation thô sơ sơ lược
            if(nameStr.length < 2) {
                alert('Tên phải nhập vào nha (ít nhất 2 cái chữ)');
                return;
            }
            if(emailStr == '') {
                alert('Nhập email vô');
                return;
            }
            if(passStr.length < 6) {
                alert('Mật khẩu 6 chữ cái trở lên');
                return;
            }
            if(passStr !== confirmPassStr) {
                alert('Hai cái mật khẩu không giống nhau ba ơi!');
                return;
            }
            if(termChk && !termChk.checked) {
                alert('Đồng ý điều khoản trước đi rùi tính tiếp');
                return;
            }

            var formData = new FormData();
            formData.append('name', nameStr);
            formData.append('email', emailStr);
            formData.append('password', passStr);
            formData.append('confirm_password', confirmPassStr);
            formData.append('terms_accepted', '1');

            regBtn.textContent = 'Đang load...';
            regBtn.disabled = true;

            fetch('../process/register-process.php', {
                method: 'POST',
                body: formData
            }).then(function(res) {
                if(res.redirected) {
                    window.location.href = res.url;
                    return;
                }
                return res.text();
            }).then(function(txt) {
                try {
                    let d = JSON.parse(txt);
                    if(d.error_type == 'email_exists') {
                        let ok = confirm("Trùng email rồi, qua đăng nhập không?");
                        if(ok) window.location.href = 'dangnhap.html';
                        else {
                            regBtn.textContent = 'Đăng ký';
                            regBtn.disabled = false;
                        }
                    } else if (d.message) {
                        alert(d.message);
                        regBtn.textContent = 'Đăng ký';
                        regBtn.disabled = false;
                    }
                } catch(e) {
                    alert("Úi lỗi ở file đăng ký");
                    regBtn.textContent = 'Đăng ký';
                    regBtn.disabled = false;
                }
            }).catch(function(err) {
                console.log(err);
                alert("lỗi fetch error");
                regBtn.textContent = 'Đăng ký';
                regBtn.disabled = false;
            });
        });
    }

    // Modal điều khoản - thay bằng alert cho sơ xài
    const termsLinks = document.querySelectorAll('.terms a');
    for(var i=0; i<termsLinks.length; i++){
        termsLinks[i].addEventListener('click', function(e) {
            e.preventDefault();
            alert("Nội dung điều khoản abc xyz... Đang viết chưa xong");
        });
    }
});
