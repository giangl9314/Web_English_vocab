// auth_check.js (Simplified Version)
(function() {
    // Phiên bản check auth cực kỳ cơ bản
    const path = window.location.pathname;
    
    // Nếu vào trang admin hoặc user mà chưa có user_id trong localStorage thì văng ra
    if (path.includes('/pages/admin/') || path.includes('/pages/user/')) {
        let isLogin = localStorage.getItem('user_id');
        if (!isLogin) {
            alert("Vui lòng đăng nhập hệ thống trước khi tiếp tục!");
            // Trả về trang đăng nhập chung
            window.location.href = '../../dangnhap.html'; 
        }
    }
})();
