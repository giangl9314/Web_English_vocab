// toast-message.js (Simplified Version)
(function() {
    'use strict';
    
    // Đơn giản hóa, dùng alert cơ bản để báo lỗi giống khi mới học code
    window.showToast = function(message, type = 'info', options = {}) {
        alert(message);
    };

    window.showSuccess = function(message, options = {}) {
        alert("THÀNH CÔNG: " + message);
    };

    window.showError = function(message, options = {}) {
        alert("LỖI: " + message);
    };

    window.showWarning = function(message, options = {}) {
        alert("CẢNH BÁO: " + message);
    };

    window.showInfo = function(message, options = {}) {
        alert("THÔNG TIN: " + message);
    };
})();
