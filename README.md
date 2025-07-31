# 照護派遣平台 (Care Dispatch Platform)

這是一個用來管理居家照護服務的平台，目標是把個案管理、排班、打卡、請假和薪資計算整合在一起，方便管理員、督導和照護人員使用。技術上用 Laravel 和 Vue.js，搭配 Docker 部署，架構簡單但能應付實際需求。專案目前是骨架，核心功能還需要實作，但基礎都打好了，接下來就是填邏輯和優化。

## 專案概述

- **目標**：簡化居家照護的排程和管理，支援多角色（超級管理員、督導、照護人員），提供打卡、請假和薪資自動化功能。
- **技術棧**：
  - 後端：Laravel 11 (PHP 8.3)
  - 前端：Vue.js 3 (Vite, Pinia, Vue Router)
  - 資料庫：MySQL 8.0
  - 伺服器：Nginx (反向代理)
  - 容器化：Docker & Docker Compose
- **架構**：
  - 前後端分離，後端提供 RESTful API，前端負責互動介面。
  - Docker 容器化，包含 Nginx、Laravel、MySQL 和 Vue 開發伺服器。
  - 使用 Laravel Sanctum 處理認證，Pinia 管理前端狀態。

## 資料庫結構

核心表格如下，涵蓋了所有業務需求：

- `users`：用戶資料，包含角色（super_admin, supervisor, caregiver 等）。
- `patients`：個案資料，關聯督導。
- `service_items`：服務項目及其價格。
- `care_plans`：照護計畫，定義服務頻率和時長。
- `assignments`：派工任務，分配給照護人員。
- `clock_records`：打卡記錄。
- `leave_requests`：請假申請。
- `compensation_rules`：薪資規則。
- `payrolls`：薪資單。

## 安裝與執行

以下是本地開發環境的建置步驟，假設你有 Docker 和 Docker Compose。

1. **Clone 專案並進入目錄**：
   ```bash
   git clone <your-repo-url>
   cd care_dispatch_platform
   ```

2. **啟動 Docker 服務**：
   ```bash
   docker-compose up --build -d
   ```
   # 註解：這會啟動 Nginx、Laravel、MySQL 和 Vue 容器，可能需要幾分鐘。

3. **安裝 Laravel 依賴並初始化資料庫**：
   ```bash
   docker-compose exec laravel_app composer install --optimize-autoloader
   docker-compose exec laravel_app php artisan migrate --seed
   docker-compose exec laravel_app php artisan key:generate
   ```
   # 註解：migrate 會建表，seed 會生成測試用戶（admin@example.com, password: password）。

4. **訪問應用程式**：
   - 網址：`http://localhost`
   - 測試帳號：
     - 超級管理員：`admin@example.com` / `password`
     - 督導：`supervisor@example.com` / `password`
     - 照護人員：`caregiver@example.com` / `password`

5. **開發時注意**：
   - Laravel API 跑在 `http://localhost/api`。
   - Vue 開發伺服器跑在 `http://localhost:5173`，由 Nginx 代理。
   - 改動後端程式碼後，可能需要重啟容器：`docker-compose restart laravel_app`。

## 核心代碼範例

以下是一些關鍵代碼片段，展示專案的設計思路。

### 後端：用戶登入 (Laravel)

`app/Http/Controllers/Auth/LoginController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // 驗證輸入
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 嘗試登入
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        // 返回用戶資料
        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete(); // 撤銷 token
        }

        return response()->json(['message' => 'Logged out successfully']);
    }
}
```

# 註解：這段程式碼處理用戶登入和登出，使用 Sanctum 管理認證。登入成功後返回用戶資料，登出時清除 session 和 token。

### 後端：API 路由 (Laravel)

`routes/api.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('sanctum:auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // 超級管理員路由
    Route::middleware('can:isSuperAdmin')->prefix('admin')->group(function () {
        Route::apiResource('users', \App\Http\Controllers\Admin\UserController::class);
    });

    // 督導路由
    Route::middleware('can:isSupervisor')->prefix('supervisor')->group(function () {
        Route::apiResource('patients', \App\Http\Controllers\Supervisor\PatientController::class);
    });

    // 照護人員路由
    Route::middleware('can:isCaregiver')->prefix('caregiver')->group(function () {
        Route::get('schedule', [\App\Http\Controllers\Caregiver\ScheduleController::class, 'index']);
    });
});
```

# 註解：路由按角色分組，使用中介軟體檢查權限。Sanctum 保護認證路由，確保只有登入用戶能訪問。

### 前端：登入頁面 (Vue)

`src/views/LoginView.vue`

```vue
<template>
  <div class="login-container">
    <h2>登入</h2>
    <form @submit.prevent="handleLogin">
      <div>
        <label for="email">電子郵件：</label>
        <input type="email" id="email" v-model="form.email" required />
      </div>
      <div>
        <label for="password">密碼：</label>
        <input type="password" id="password" v-model="form.password" required />
      </div>
      <button type="submit" :disabled="authStore.loading">登入</button>
      <p v-if="authStore.error" class="error-message">{{ authStore.error }}</p>
    </form>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const form = reactive({
  email: '',
  password: '',
});

async function handleLogin() {
  await authStore.login(form); // 呼叫 Pinia 的 login 方法
}
</script>

<style scoped>
.login-container {
  max-width: 400px;
  margin: 50px auto;
  padding: 20px;
  border: 1px solid #ccc;
  border-radius: 8px;
}
.error-message {
  color: red;
  margin-top: 10px;
}
</style>
```

# 註解：這個 Vue 組件提供登入表單，與 Pinia 的 auth store 互動，發送登入請求到後端。

### 前端：認證狀態管理 (Pinia)

`src/stores/auth.js`

```javascript
import { defineStore } from 'pinia';
import axios from 'axios';
import router from '../router';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        loading: false,
        error: null,
    }),
    getters: {
        isLoggedIn: (state) => !!state.user,
        isSuperAdmin: (state) => state.user?.role === 'super_admin',
        isSupervisor: (state) => state.user?.role === 'supervisor',
        isCaregiver: (state) => ['caregiver', 'nutritionist', 'physiotherapist'].includes(state.user?.role),
    },
    actions: {
        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                await axios.get(`${API_BASE_URL}/sanctum/csrf-cookie`); // 獲取 CSRF cookie
                const response = await axios.post(`${API_BASE_URL}/login`, credentials);
                this.user = response.data.user;
                localStorage.setItem('user', JSON.stringify(this.user));
                // 根據角色導向
                if (this.isSuperAdmin) router.push({ name: 'AdminDashboard' });
                else if (this.isSupervisor) router.push({ name: 'SupervisorDashboard' });
                else if (this.isCaregiver) router.push({ name: 'CaregiverSchedule' });
            } catch (error) {
                this.error = error.response?.data?.message || '登入失敗';
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            this.loading = true;
            try {
                await axios.post(`${API_BASE_URL}/logout`);
                this.user = null;
                localStorage.removeItem('user');
                router.push('/login');
            } catch (error) {
                this.error = error.response?.data?.message || '登出失敗';
                throw error;
            } finally {
                this.loading = false;
            }
        },
    },
});
```

# 註解：Pinia 管理用戶狀態，處理登入和登出邏輯，並將用戶資訊存到 localStorage。

## 目前狀態與待辦事項

目前專案是個骨架，基礎架構和資料模型都齊了，但功能還沒實作完。以下是待辦事項：

1. **實作控制器邏輯**：
   - 像 `Admin/UserController.php`、`Supervisor/PatientController.php` 這些控制器需要加上 CRUD 邏輯。
   - 薪資計算邏輯（根據 `clock_records` 和 `compensation_rules` 生成 `payrolls`）也要實作。

2. **完善前端視圖**：
   - Vue 視圖（例如 `admin/UserManagementView.vue`）需要跟後端 API 串接，實現數據顯示和表單提交。
   - `CaregiverSchedule.vue` 的 FullCalendar 要從 `/api/caregiver/schedule` 動態拉數據。

3. **生產環境配置**：
   - 編譯 Vue 靜態檔案（`npm run build`），調整 Nginx 指向 `vue_app/dist`。
   - 加上 HTTPS 和環境變數加密。

4. **自動化功能**：
   - 用 Laravel 的 `schedule` 實現薪資單自動生成。
   - 清理過期的 `personal_access_tokens`。

5. **測試與錯誤處理**：
   - 加上 PHPUnit 測試後端邏輯，Vitest 測試 Vue 組件。
   - 加強錯誤提示（例如 API 失敗或網路問題）。

## 問題排查

- **容器啟動失敗**：檢查 Docker 是否正常運行，確認端口（80, 3306, 5173, 9000）沒被佔用。
- **API 請求 404**：確認 Nginx 配置（`nginx/default.conf`）和 Laravel 路由（`routes/api.php`）。
- **Vue 頁面空白**：檢查 `vite.config.js` 的代理設定，或重啟 Vite 容器。
- **資料庫連線錯誤**：確認 `laravel/.env` 的 DB_HOST 是 `mysql_db`，並檢查 MySQL 容器是否正常。

## 結語

這專案的架構穩固，技術棧也都是業界常用的，適合快速開發和迭代。雖然現在是骨架，但只要把邏輯補上，就能變成一個實用的照護管理系統。如果有問題，直接在 issue 裡提，會盡快處理。
