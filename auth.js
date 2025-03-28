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
async function checkAuth() {
    console.log('Checking auth...');
    const token = localStorage.getItem('authToken');
    if (!token) {
        console.log('No token found, redirecting to login...');
        window.location.href = 'admin.html';
        return false;
    }
    return true;
}

// Функция для входа
async function login(username, password) {
    console.log('Login attempt for user:', username);
    try {
        // Хешируем введенный пароль
        console.log('Hashing password...');
        const hashedPassword = await sha256(password);
        console.log('Hashed password:', hashedPassword);
        console.log('Expected hash:', config.credentials.passwordHash);
        
        if (username === config.credentials.username && 
            hashedPassword === config.credentials.passwordHash) {
            console.log('Credentials match!');
            
            // Создаем JWT токен
            console.log('Generating token...');
            const token = await generateToken(username);
            console.log('Token generated:', token);
            
            localStorage.setItem('authToken', token);
            localStorage.setItem('isAuthenticated', 'true');
            console.log('Auth data saved to localStorage');
            
            return true;
        }
        console.log('Credentials do not match');
        return false;
    } catch (error) {
        console.error('Login error:', error);
        return false;
    }
}

// Функция для выхода
function logout() {
    console.log('Logging out...');
    localStorage.removeItem('authToken');
    localStorage.removeItem('isAuthenticated');
    window.location.href = 'admin.html';
}

// Функция для генерации JWT токена
async function generateToken(username) {
    console.log('Generating token for user:', username);
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
    
    console.log('Generating signature...');
    const signature = await sha256(encodedHeader + "." + encodedPayload + "." + config.secretKey);
    
    return encodedHeader + "." + encodedPayload + "." + signature;
}

// Функция для проверки JWT токена
async function verifyToken(token) {
    console.log('Verifying token...');
    try {
        const [header, payload, signature] = token.split('.');
        const decodedPayload = JSON.parse(atob(payload));
        console.log('Decoded payload:', decodedPayload);
        
        // Проверяем срок действия токена
        if (decodedPayload.exp < Math.floor(Date.now() / 1000)) {
            console.log('Token expired');
            return false;
        }
        
        // Проверяем подпись
        console.log('Verifying signature...');
        const expectedSignature = await sha256(header + "." + payload + "." + config.secretKey);
        const isValid = signature === expectedSignature;
        console.log('Signature valid:', isValid);
        return isValid;
    } catch (error) {
        console.error('Token verification error:', error);
        return false;
    }
}

// Функция для хеширования SHA-256
async function sha256(message) {
    console.log('Hashing message:', message);
    // Используем Web Crypto API для хеширования
    const encoder = new TextEncoder();
    const data = encoder.encode(message);
    const hash = await crypto.subtle.digest('SHA-256', data);
    const result = Array.from(new Uint8Array(hash))
        .map(b => b.toString(16).padStart(2, '0'))
        .join('');
    console.log('Hash result:', result);
    return result;
}

// Экспортируем функции
window.auth = {
    checkAuth,
    login,
    logout,
    verifyToken
}; 