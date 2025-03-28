// Конфигурация
const config = {
    secretKey: 'your-secret-key-here', // В продакшене использовать безопасный ключ
    tokenExpiration: '24h',
    credentials: {
        username: 'olegp',
        // Хеш пароля 458555
        passwordHash: '9c7b8a341dd477eebe11dce4e2a70446f700f59d3177d22d237cb6b58f920be3'
    }
};

// Функция для проверки аутентификации
async function checkAuth() {
    const token = localStorage.getItem('authToken');
    if (!token) {
        window.location.href = 'admin.html';
        return false;
    }
    return true;
}

// Функция для входа
async function login(username, password) {
    try {
        const hashedPassword = await sha256(password);
        
        if (username === config.credentials.username && 
            hashedPassword === config.credentials.passwordHash) {
            
            const token = await generateToken(username);
            localStorage.setItem('authToken', token);
            localStorage.setItem('isAuthenticated', 'true');
            
            return true;
        }
        return false;
    } catch (error) {
        console.error('Authentication error');
        return false;
    }
}

// Функция для выхода
function logout() {
    localStorage.removeItem('authToken');
    localStorage.removeItem('isAuthenticated');
    window.location.href = 'admin.html';
}

// Функция для генерации JWT токена
async function generateToken(username) {
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
    
    const signature = await sha256(encodedHeader + "." + encodedPayload + "." + config.secretKey);
    
    return encodedHeader + "." + encodedPayload + "." + signature;
}

// Функция для проверки JWT токена
async function verifyToken(token) {
    try {
        const [header, payload, signature] = token.split('.');
        const decodedPayload = JSON.parse(atob(payload));
        
        // Проверяем срок действия токена
        if (decodedPayload.exp < Math.floor(Date.now() / 1000)) {
            return false;
        }
        
        // Проверяем подпись
        const expectedSignature = await sha256(header + "." + payload + "." + config.secretKey);
        return signature === expectedSignature;
    } catch (error) {
        return false;
    }
}

// Функция для хеширования SHA-256
async function sha256(message) {
    const encoder = new TextEncoder();
    const data = encoder.encode(message);
    const hash = await crypto.subtle.digest('SHA-256', data);
    return Array.from(new Uint8Array(hash))
        .map(b => b.toString(16).padStart(2, '0'))
        .join('');
}

// Экспортируем функции
window.auth = {
    checkAuth,
    login,
    logout,
    verifyToken
}; 