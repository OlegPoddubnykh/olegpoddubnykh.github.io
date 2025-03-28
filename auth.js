// Конфигурация
const config = {
    secretKey: 'your-secret-key-here', // В продакшене использовать безопасный ключ
    tokenExpiration: '24h',
    credentials: {
        username: 'olegp',
        // Хеш пароля 458555
        passwordHash: '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8'
    }
};

// Функция для проверки аутентификации
function checkAuth() {
    const token = localStorage.getItem('authToken');
    if (!token) {
        window.location.href = 'admin.html';
        return false;
    }
    return true;
}

// Функция для входа
function login(username, password) {
    // Хешируем введенный пароль
    const hashedPassword = sha256(password);
    
    if (username === config.credentials.username && 
        hashedPassword === config.credentials.passwordHash) {
        
        // Создаем JWT токен
        const token = generateToken(username);
        localStorage.setItem('authToken', token);
        localStorage.setItem('isAuthenticated', 'true');
        
        return true;
    }
    return false;
}

// Функция для выхода
function logout() {
    localStorage.removeItem('authToken');
    localStorage.removeItem('isAuthenticated');
    window.location.href = 'admin.html';
}

// Функция для генерации JWT токена
function generateToken(username) {
    const header = {
        "alg": "HS256",
        "typ": "JWT"
    };
    
    const payload = {
        "sub": username,
        "iat": Math.floor(Date.now() / 1000),
        "exp": Math.floor(Date.now() / 1000) + (24 * 60 * 60) // 24 часа
    };
    
    const encodedHeader = btoa(JSON.stringify(header));
    const encodedPayload = btoa(JSON.stringify(payload));
    
    const signature = sha256(encodedHeader + "." + encodedPayload + "." + config.secretKey);
    
    return encodedHeader + "." + encodedPayload + "." + signature;
}

// Функция для проверки JWT токена
function verifyToken(token) {
    try {
        const [header, payload, signature] = token.split('.');
        const decodedPayload = JSON.parse(atob(payload));
        
        // Проверяем срок действия токена
        if (decodedPayload.exp < Math.floor(Date.now() / 1000)) {
            return false;
        }
        
        // Проверяем подпись
        const expectedSignature = sha256(header + "." + payload + "." + config.secretKey);
        return signature === expectedSignature;
    } catch (error) {
        return false;
    }
}

// Функция для хеширования SHA-256
function sha256(message) {
    // Используем Web Crypto API для хеширования
    const encoder = new TextEncoder();
    const data = encoder.encode(message);
    return crypto.subtle.digest('SHA-256', data)
        .then(hash => {
            return Array.from(new Uint8Array(hash))
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');
        });
}

// Экспортируем функции
window.auth = {
    checkAuth,
    login,
    logout,
    verifyToken
}; 