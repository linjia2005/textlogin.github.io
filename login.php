<?php
// 数据库配置
$dbHost = '127.0.0.1';
$dbName = '网站';
$dbUser = 'root';
$dbPass = 'root';

try {
    // 创建PDO连接
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 创建用户表（如果不存在）
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 登录成功标记
$loginSuccess = false;

// 处理注册请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];
    
    $errors = [];
    
    if (!$email) {
        $errors[] = "请输入有效的电子邮箱";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "密码长度至少需要8个字符";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "两次输入的密码不匹配";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([
                'email' => $email,
                'password' => $hashedPassword
            ]);
            $registrationSuccess = true;
        } catch(PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = "该电子邮箱已被注册";
            } else {
                $errors[] = "注册失败，请重试";
            }
        }
    }
}

// 处理登录请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST["password"];
    
    $loginError = null;
    
    if (!$email) {
        $loginError = "请输入有效的电子邮箱";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $loginSuccess = true;
            } else {
                if ($user) {
                    $loginError = "密码错误";
                } else {
                    $loginError = "该电子邮箱未注册";
                }
            }
        } catch(PDOException $e) {
            $loginError = "登录失败，请重试";
        }
    }
}

if ($loginSuccess) {
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>登录成功</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
        <style type="text/tailwindcss">
            @layer utilities {
                .content-auto {
                    content-visibility: auto;
                }
                .success-animation {
                    animation: success-pulse 1.5s ease-in-out infinite;
                }
                @keyframes success-pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                    100% { transform: scale(1); }
                }
                .countdown-animation {
                    animation: countdown-shrink 1s ease-in-out forwards;
                }
                @keyframes countdown-shrink {
                    0% { transform: scale(1.2); }
                    100% { transform: scale(1); }
                }
            }
        </style>
    </head>
    <body class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 transform transition-all duration-500 hover:shadow-4xl">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 mb-4 success-animation">
                    <i class="fa fa-check text-4xl text-green-500 dark:text-green-400"></i>
                </div>
                <h2 class="text-[clamp(1.5rem,3vw,2rem)] font-bold text-gray-800 dark:text-white mb-2">登录成功！</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    即将在 <span id="countdown" class="inline-block font-bold text-primary">3</span> 秒后返回主页
                </p>
                <div class="w-full h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div id="progress-bar" class="h-full bg-primary rounded-full transition-all duration-3000" style="width: 100%"></div>
                </div>
            </div>
            <div class="flex justify-center">
                <a href="index.html" class="inline-flex items-center text-primary hover:text-primary/80 transition-colors duration-300">
                    <span>如果您的浏览器没有自动跳转，请点击这里</span>
                    <i class="fa fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
        <script>
            let seconds = 3;
            const countdownEl = document.getElementById("countdown");
            const progressBar = document.getElementById("progress-bar");
            
            const timer = setInterval(() => {
                seconds--;
                countdownEl.textContent = seconds;
                countdownEl.classList.add("countdown-animation");
                setTimeout(() => {
                    countdownEl.classList.remove("countdown-animation");
                }, 1000);
                
                if (seconds <= 0) {
                    clearInterval(timer);
                    window.location.href = "index.html";
                }
            }, 1000);
            
            // 进度条动画
            setTimeout(() => {
                progressBar.style.width = "0%";
            }, 50);
        </script>
    </body>
    </html>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户登录与注册</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#165DFF',
                        secondary: '#36CFC9',
                        accent: '#7B61FF',
                        success: '#10B981',
                        warning: '#F59E0B',
                        danger: '#EF4444',
                        neutral: {
                            50: '#F9FAFB',
                            100: '#F3F4F6',
                            200: '#E5E7EB',
                            300: '#D1D5DB',
                            400: '#9CA3AF',
                            500: '#6B7280',
                            600: '#4B5563',
                            700: '#374151',
                            800: '#1F2937',
                            900: '#111827',
                        }
                    },
                    fontFamily: {
                        inter: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        'soft': '0 10px 30px -5px rgba(0, 0, 0, 0.05)',
                        'medium': '0 10px 30px -5px rgba(0, 0, 0, 0.1)',
                        'hard': '0 20px 40px -5px rgba(0, 0, 0, 0.15)',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'pulse-soft': 'pulseSoft 2s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        pulseSoft: {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.7' },
                        }
                    }
                },
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .content-auto {
                content-visibility: auto;
            }
            .bg-glass {
                background: rgba(255, 255, 255, 0.25);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }
            .bg-glass-dark {
                background: rgba(17, 24, 39, 0.7);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }
            .text-gradient {
                background-clip: text;
                -webkit-background-clip: text;
                color: transparent;
                background-image: linear-gradient(135deg, #165DFF 0%, #36CFC9 100%);
            }
            .input-focus {
                @apply border-primary ring-2 ring-primary/20;
            }
            .btn-hover {
                @apply transform -translate-y-1 shadow-lg;
            }
            .card-hover {
                @apply hover:shadow-hard hover:-translate-y-1 transition-all duration-300;
            }
            .tab-active {
                @apply text-primary border-b-2 border-primary;
            }
            .tab-inactive {
                @apply text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300;
            }
        }
    </style>
</head>
<body class="font-inter bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 min-h-screen flex items-center justify-center p-4">
    <!-- 背景装饰 -->
    <div class="fixed inset-0 overflow-hidden -z-10">
        <div class="absolute top-[-100px] right-[-100px] w-[400px] h-[400px] rounded-full bg-primary/5 dark:bg-primary/10 blur-3xl"></div>
        <div class="absolute bottom-[-100px] left-[-100px] w-[400px] h-[400px] rounded-full bg-secondary/5 dark:bg-secondary/10 blur-3xl"></div>
    </div>
    
    <!-- 深色模式切换按钮 -->
    <button id="themeToggle" class="fixed top-6 right-6 z-50 bg-white/80 dark:bg-neutral-800/80 backdrop-blur-sm rounded-full p-3 shadow-medium text-neutral-700 dark:text-white transition-all duration-300 hover:scale-110">
        <i class="fa fa-moon-o dark:hidden text-xl"></i>
        <i class="fa fa-sun-o hidden dark:inline-block text-xl"></i>
    </button>

    <!-- 主容器 -->
    <div class="max-w-5xl w-full bg-white dark:bg-gray-800 rounded-3xl shadow-medium overflow-hidden transition-all duration-500">
        <div class="flex flex-col md:flex-row">
            <!-- 左侧品牌展示区域 -->
            <div class="w-full md:w-2/5 bg-gradient-to-br from-primary to-accent text-white relative overflow-hidden p-8 md:p-12 flex flex-col justify-between">
                <!-- 装饰元素 -->
                <div class="absolute inset-0 overflow-hidden opacity-20">
                    <div class="absolute -top-40 -right-40 w-80 h-80 rounded-full bg-white/30 blur-3xl"></div>
                    <div class="absolute top-1/2 -left-20 w-60 h-60 rounded-full bg-white/20 blur-3xl"></div>
                    <div class="absolute -bottom-20 -right-20 w-72 h-72 rounded-full bg-white/20 blur-3xl"></div>
                </div>
                
                <!-- 顶部Logo -->
                <div class="flex items-center space-x-4 mb-12 animate-fade-in">
                    <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center">
                        <i class="fa fa-rocket text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">快捷导航</h1>
                </div>
                
                <!-- 中间内容 -->
                <div class="space-y-8 animate-slide-up">
                    <h2 class="text-[clamp(1.8rem,3vw,2.5rem)] font-bold leading-tight">
                        发现全新的<br>网络体验
                    </h2>
                    <p class="text-white/80 max-w-md">
                        登录您的账户，获取个性化的快捷导航体验，让您的网络世界更加高效便捷。
                    </p>
                    
                    <!-- 特点列表 -->
                    <div class="space-y-6 mt-10">
                        <div class="flex items-start space-x-4">
                            <div class="mt-1 w-8 h-8 rounded-full bg-white/10 flex items-center justify-center">
                                <i class="fa fa-star text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-lg">个性化收藏</h3>
                                <p class="text-white/70 text-sm mt-1">保存您最常访问的网站和服务</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <div class="mt-1 w-8 h-8 rounded-full bg-white/10 flex items-center justify-center">
                                <i class="fa fa-search text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-lg">多引擎搜索</h3>
                                <p class="text-white/70 text-sm mt-1">一键在多个搜索引擎间切换</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <div class="mt-1 w-8 h-8 rounded-full bg-white/10 flex items-center justify-center">
                                <i class="fa fa-cloud text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-lg">数据同步</h3>
                                <p class="text-white/70 text-sm mt-1">在所有设备上保持您的个性化设置</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 底部信息 -->
                <div class="mt-auto text-white/60 text-sm animate-fade-in" style="animation-delay: 0.3s">
                    © 2023 快捷导航. 保留所有权利.
                </div>
            </div>
            
            <!-- 右侧表单区域 -->
            <div class="w-full md:w-3/5 p-8 md:p-12 flex items-center justify-center">
                <div class="w-full max-w-md">
                    <!-- 选项卡切换 -->
                    <div class="flex border-b border-neutral-200 dark:border-neutral-700 mb-8">
                        <button id="loginTab" class="py-3 px-6 text-lg font-medium tab-active">
                            登录
                        </button>
                        <button id="registerTab" class="py-3 px-6 text-lg font-medium tab-inactive">
                            注册
                        </button>
                    </div>
                    
                    <!-- 登录表单 -->
                    <div id="loginForm" class="space-y-8 animate-fade-in">
                        <h2 class="text-2xl font-bold text-neutral-800 dark:text-white">
                            欢迎回来
                        </h2>
                        <p class="text-neutral-500 dark:text-neutral-400">
                            请登录您的账户继续
                        </p>
                        
                        <?php if (isset($loginError)): ?>
                            <div class="p-4 bg-danger/10 dark:bg-danger/20 border border-danger/20 dark:border-danger/40 rounded-xl text-danger flex items-start">
                                <i class="fa fa-exclamation-circle mt-1 mr-3 text-lg"></i>
                                <div>
                                    <h4 class="font-medium">登录失败</h4>
                                    <p class="text-sm mt-1"><?php echo $loginError; ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form class="mt-8 space-y-6" method="POST">
                            <!-- 邮箱输入框 -->
                            <div class="space-y-2">
                                <label for="login_email" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                    电子邮箱
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fa fa-envelope-o text-neutral-400"></i>
                                    </div>
                                    <input id="login_email" name="email" type="email" autocomplete="email" required
                                        class="block w-full pl-12 pr-4 py-4 bg-neutral-50 dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded-xl focus:outline-none focus:input-focus transition-all duration-300 dark:text-white placeholder:text-neutral-400"
                                        placeholder="your@email.com">
                                </div>
                            </div>
                            
                            <!-- 密码输入框 -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label for="login_password" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                        密码
                                    </label>
                                    <a href="#" class="text-sm text-primary hover:text-primary/80 transition-colors duration-300">
                                        忘记密码?
                                    </a>
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fa fa-lock text-neutral-400"></i>
                                    </div>
                                    <input id="login_password" name="password" type="password" autocomplete="current-password" required
                                        class="block w-full pl-12 pr-12 py-4 bg-neutral-50 dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded-xl focus:outline-none focus:input-focus transition-all duration-300 dark:text-white placeholder:text-neutral-400"
                                        placeholder="••••••••">
                                    <button type="button" id="toggleLoginPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                        <i class="fa fa-eye text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 transition-colors duration-300"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- 记住我 -->
                            <div class="flex items-center">
                                <input id="remember-me" name="remember-me" type="checkbox"
                                    class="h-5 w-5 text-primary focus:ring-primary border-neutral-300 rounded transition-colors duration-300">
                                <label for="remember-me" class="ml-2 block text-sm text-neutral-600 dark:text-neutral-400">
                                    记住我的登录状态
                                </label>
                            </div>
                            
                            <!-- 登录按钮 -->
                            <div>
                                <button type="submit" name="login"
                                    class="w-full bg-primary hover:bg-primary/90 text-white font-medium py-4 px-6 rounded-xl transition-all duration-300 transform hover:btn-hover flex items-center justify-center space-x-2 shadow-md hover:shadow-lg">
                                    <span>登录</span>
                                    <i class="fa fa-arrow-right"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- 分隔线 -->
                        <div class="my-8 flex items-center">
                            <div class="flex-grow h-px bg-neutral-200 dark:bg-neutral-700"></div>
                            <span class="flex-shrink mx-4 text-sm text-neutral-500 dark:text-neutral-400">或者</span>
                            <div class="flex-grow h-px bg-neutral-200 dark:bg-neutral-700"></div>
                        </div>
                        
                        <!-- 社交媒体登录 -->
                        <div class="grid grid-cols-3 gap-4">
                            <a href="#" class="flex items-center justify-center p-4 border border-neutral-200 dark:border-neutral-600 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-all duration-300 shadow-sm hover:shadow-md">
                                <i class="fa fa-google text-neutral-700 dark:text-white text-lg"></i>
                            </a>
                            <a href="#" class="flex items-center justify-center p-4 border border-neutral-200 dark:border-neutral-600 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-all duration-300 shadow-sm hover:shadow-md">
                                <i class="fa fa-github text-neutral-700 dark:text-white text-lg"></i>
                            </a>
                            <a href="#" class="flex items-center justify-center p-4 border border-neutral-200 dark:border-neutral-600 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-all duration-300 shadow-sm hover:shadow-md">
                                <i class="fa fa-weixin text-neutral-700 dark:text-white text-lg"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- 注册表单 -->
                    <div id="registerForm" class="space-y-8 hidden">
                        <h2 class="text-2xl font-bold text-neutral-800 dark:text-white">
                            创建新账户
                        </h2>
                        <p class="text-neutral-500 dark:text-neutral-400">
                            填写以下信息注册您的账户
                        </p>
                        
                        <?php if (isset($errors) && !empty($errors)): ?>
                            <div class="p-4 bg-danger/10 dark:bg-danger/20 border border-danger/20 dark:border-danger/40 rounded-xl text-danger flex items-start">
                                <i class="fa fa-exclamation-circle mt-1 mr-3 text-lg"></i>
                                <div>
                                    <h4 class="font-medium">注册失败</h4>
                                    <ul class="text-sm mt-1 space-y-1">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($registrationSuccess) && $registrationSuccess): ?>
                            <div class="p-4 bg-success/10 dark:bg-success/20 border border-success/20 dark:border-success/40 rounded-xl text-success flex items-start">
                                <i class="fa fa-check-circle mt-1 mr-3 text-lg"></i>
                                <div>
                                    <h4 class="font-medium">注册成功</h4>
                                    <p class="text-sm mt-1">请使用您的账户信息登录</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form class="mt-8 space-y-6" method="POST">
                            <!-- 邮箱输入框 -->
                            <div class="space-y-2">
                                <label for="register_email" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                    电子邮箱
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fa fa-envelope-o text-neutral-400"></i>
                                    </div>
                                    <input id="register_email" name="email" type="email" autocomplete="email" required
                                        class="block w-full pl-12 pr-4 py-4 bg-neutral-50 dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded-xl focus:outline-none focus:input-focus transition-all duration-300 dark:text-white placeholder:text-neutral-400"
                                        placeholder="your@email.com">
                                </div>
                            </div>
                            
                            <!-- 密码输入框 -->
                            <div class="space-y-2">
                                <label for="register_password" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                    密码
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fa fa-lock text-neutral-400"></i>
                                    </div>
                                    <input id="register_password" name="password" type="password" autocomplete="new-password" required
                                        class="block w-full pl-12 pr-12 py-4 bg-neutral-50 dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded-xl focus:outline-none focus:input-focus transition-all duration-300 dark:text-white placeholder:text-neutral-400"
                                        placeholder="至少8个字符">
                                    <button type="button" id="toggleRegisterPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                        <i class="fa fa-eye text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 transition-colors duration-300"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- 确认密码输入框 -->
                            <div class="space-y-2">
                                <label for="register_confirm_password" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                    确认密码
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fa fa-lock text-neutral-400"></i>
                                    </div>
                                    <input id="register_confirm_password" name="confirm_password" type="password" autocomplete="new-password" required
                                        class="block w-full pl-12 pr-12 py-4 bg-neutral-50 dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded-xl focus:outline-none focus:input-focus transition-all duration-300 dark:text-white placeholder:text-neutral-400"
                                        placeholder="再次输入密码">
                                    <button type="button" id="toggleRegisterConfirmPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                        <i class="fa fa-eye text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 transition-colors duration-300"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- 注册按钮 -->
                            <div>
                                <button type="submit" name="register"
                                    class="w-full bg-primary hover:bg-primary/90 text-white font-medium py-4 px-6 rounded-xl transition-all duration-300 transform hover:btn-hover flex items-center justify-center space-x-2 shadow-md hover:shadow-lg">
                                    <span>注册</span>
                                    <i class="fa fa-user-plus"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 深色模式切换
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        
        // 检查用户偏好
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
        
        // 切换主题
        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            if (html.classList.contains('dark')) {
                localStorage.theme = 'dark';
            } else {
                localStorage.theme = 'light';
            }
            
            // 添加动画效果
            themeToggle.classList.add('scale-90');
            setTimeout(() => {
                themeToggle.classList.remove('scale-90');
            }, 200);
        });
        
        // 密码显示/隐藏切换
        function setupPasswordToggle(toggleBtn, passwordInput) {
            toggleBtn.addEventListener('click', () => {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // 切换图标
                const icon = toggleBtn.querySelector('i');
                if (type === 'text') {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
                
                // 添加动画效果
                icon.classList.add('scale-110');
                setTimeout(() => {
                    icon.classList.remove('scale-110');
                }, 200);
            });
        }
        
        // 设置密码切换功能
        const toggleLoginPassword = document.getElementById('toggleLoginPassword');
        const loginPasswordInput = document.getElementById('login_password');
        if (toggleLoginPassword && loginPasswordInput) {
            setupPasswordToggle(toggleLoginPassword, loginPasswordInput);
        }
        
        const toggleRegisterPassword = document.getElementById('toggleRegisterPassword');
        const registerPasswordInput = document.getElementById('register_password');
        if (toggleRegisterPassword && registerPasswordInput) {
            setupPasswordToggle(toggleRegisterPassword, registerPasswordInput);
        }
        
        const toggleRegisterConfirmPassword = document.getElementById('toggleRegisterConfirmPassword');
        const registerConfirmPasswordInput = document.getElementById('register_confirm_password');
        if (toggleRegisterConfirmPassword && registerConfirmPasswordInput) {
            setupPasswordToggle(toggleRegisterConfirmPassword, registerConfirmPasswordInput);
        }
        
        // 表单切换
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        
        function switchToLogin() {
            loginTab.classList.add('tab-active');
            loginTab.classList.remove('tab-inactive');
            registerTab.classList.remove('tab-active');
            registerTab.classList.add('tab-inactive');
            loginForm.classList.remove('hidden');
            loginForm.classList.add('animate-fade-in');
            registerForm.classList.add('hidden');
        }
        
        function switchToRegister() {
            registerTab.classList.add('tab-active');
            registerTab.classList.remove('tab-inactive');
            loginTab.classList.remove('tab-active');
            loginTab.classList.add('tab-inactive');
            registerForm.classList.remove('hidden');
            registerForm.classList.add('animate-fade-in');
            loginForm.classList.add('hidden');
        }
        
        loginTab.addEventListener('click', switchToLogin);
        registerTab.addEventListener('click', switchToRegister);
        
        // 自动聚焦第一个输入框
        if (loginForm && !loginForm.classList.contains('hidden')) {
            setTimeout(() => {
                const firstInput = loginForm.querySelector('input');
                if (firstInput) firstInput.focus();
            }, 300);
        }
    </script>
</body>
</html>