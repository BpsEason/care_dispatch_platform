# 照護派遣平台 (Care Dispatch Platform)

這是一個居家照護服務的管理平台，目標是把個案管理、排班、打卡、請假和薪資計算整合起來，方便超級管理員、督導和照護人員使用。專案用 Laravel 11 做後端，Vue.js 3 做前端，搭配 Docker 部署。目前倉庫只包含部分關鍵代碼，完整結構需透過 `care_dispatch_platform.sh` 腳本生成，然後手動補充套件和邏輯。架構已經打好，接下來就是實作業務功能。

## 專案概述

- **目標**：簡化居家照護的排程和管理，支援多角色（超級管理員、督導、照護人員），提供考勤、請假和薪資自動化功能。
- **技術棧**：
  - 後端：Laravel 11 (PHP 8.3)
  - 前端：Vue.js 3 (Vite, Pinia, Vue Router)
  - 資料庫：MySQL 8.0
  - 伺服器：Nginx (反向代理)
  - 容器化：Docker & Docker Compose
- **架構**：
  - 前後端分離，後端提供 RESTful API，前端負責互動介面。
  - 使用 Laravel Sanctum 處理認證，Pinia 管理前端狀態。
  - Docker 容器化，確保環境一致性。

## 資料庫結構

核心表格如下，涵蓋所有業務需求：

- `users`：用戶資料，包含角色（`super_admin`, `supervisor`, `caregiver`, `nutritionist`, `physiotherapist`）。
- `patients`：個案資料，關聯督導。
- `service_items`：服務項目及其價格。
- `care_plans`：照護計畫，定義服務頻率和時長。
- `assignments`：派工任務，分配給照護人員。
- `clock_records`：打卡記錄。
- `leave_requests`：請假申請。
- `compensation_rules`：薪資規則。
- `payrolls`：薪資單。

## 下載與設置

目前倉庫只包含部分關鍵代碼，完整專案結構需透過 `care_dispatch_platform.sh` 腳本生成。以下是下載、設置和補充套件的步驟：

### 1. 下載倉庫
```bash
git clone https://github.com/BpsEason/care_dispatch_platform.git
cd care_dispatch_platform
```

## 註解：如果倉庫是空的，可以直接跳到步驟 2 使用腳本生成結構。

### 2. 運行腳本生成專案結構
假設你有 `care_dispatch_platform.sh` 腳本（需另行取得，可能在內部共享或自行保存）：
```bash
chmod +x care_dispatch_platform.sh
./care_dispatch_platform.sh
```

## 註解：腳本會生成 `docker-compose.yml`、Laravel 後端 (`laravel/`)、Vue 前端 (`vue_app/`) 和 Nginx 配置 (`nginx/`)。

### 3. 安裝依賴
專案需要手動安裝 Composer 和 npm 套件：

#### 後端依賴 (Laravel)
```bash
docker-compose up -d  # 先啟動容器
docker-compose exec laravel_app composer install --optimize-autoloader
docker-compose exec laravel_app php artisan key:generate
```

## 註解：這會安裝 Laravel 的依賴（如 `laravel/sanctum`）並生成應用程式金鑰。

#### 前端依賴 (Vue.js)
```bash
docker-compose exec vue_app_dev npm install
```

## 註解：這會安裝 Vue.js 的依賴（如 `vue`, `pinia`, `axios`, `@fullcalendar/*`）。

### 4. 初始化資料庫
```bash
docker-compose exec laravel_app php artisan migrate --seed
```

## 註解：這會創建資料表並插入測試用戶（`admin@example.com`, `supervisor@example.com`, `caregiver@example.com`，密碼均為 `password`）。

### 5. 啟動應用程式
```bash
docker-compose up -d
```

- 訪問網址：`http://localhost`
- 測試帳號：
  - 超級管理員：`admin@example.com` / `password`
  - 督導：`supervisor@example.com` / `password`
  - 照護人員：`caregiver@example.com` / `password`

## 註解：API 請求走 `http://localhost/api`，前端開發伺服器跑在 `http://localhost:5173`（由 Nginx 代理）。

## 核心代碼範例

以下是關鍵代碼片段，展示專案的核心功能和設計思路。倉庫目前只有部分代碼，完整結構需靠腳本生成。

### 後端：用戶登入 (Laravel)

`laravel/app/Http/Controllers/Auth/LoginController.php`

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
        // 驗證輸入的 email 和 password
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 嘗試登入，失敗則拋出錯誤
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        // 成功則返回用戶資料
        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        // 清除 session 和當前 token
        Auth::guard('web')->logout();
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->json(['message' => 'Logged out successfully']);
    }
}
```

## 註解：這段程式碼處理用戶登入和登出，使用 Sanctum 管理認證，確保 API 安全。

### 後端：API 路由 (Laravel)

`laravel/routes/api.php`

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

## 註解：路由按角色分組，使用中介軟體檢查權限，確保只有正確角色能訪問對應 API。

### 前端：登入頁面 (Vue)

`vue_app/src/views/LoginView.vue`

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
  await authStore.login(form); // 呼叫 Pinia 的 login 方法發送登入請求
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

## 註解：這個 Vue 組件提供簡單的登入表單，與 Pinia 的 auth store 串接，發送請求到後端。

### 前端：認證狀態管理 (Pinia)

`vue_app/src/stores/auth.js`

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
                // 先獲取 CSRF cookie
                await axios.get(`${API_BASE_URL}/sanctum/csrf-cookie`);
                const response = await axios.post(`${API_BASE_URL}/login`, credentials);
                this.user = response.data.user;
                localStorage.setItem('user', JSON.stringify(this.user));
                // 根據角色導向對應頁面
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

## 註解：Pinia 管理用戶狀態，處理登入和登出，並將用戶資訊存到 localStorage，確保頁面刷新後狀態不丟失。

## 手動補充套件與程式碼

目前倉庫可能只包含部分代碼，完整專案需靠腳本生成。以下是手動補充的指引：

### 1. 檢查必要檔案
確保以下檔案存在（由 `care_dispatch_platform.sh` 生成）：
- `docker-compose.yml`
- `laravel/`：包含 `Dockerfile`、`.env`、控制器、模型、遷移等。
- `vue_app/`：包含 `Dockerfile.dev`、`package.json`、`vite.config.js`、前端程式碼。
- `nginx/`：包含 `default.conf`。

如果缺少檔案，重新運行腳本或從備份復原。

### 2. 補充依賴
腳本生成的 `laravel/composer.json` 和 `vue_app/package.json` 可能未包含所有依賴，需手動添加：

#### 後端 (Laravel)
編輯 `laravel/composer.json`（如果不存在，創建以下內容）：
```json
{
    "require": {
        "php": "^8.3",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "phpunit/phpunit": "^11.0"
    }
}
```

然後運行：
```bash
docker-compose exec laravel_app composer install
```

#### 前端 (Vue.js)
確認 `vue_app/package.json` 包含以下依賴：
```json
{
  "dependencies": {
    "@fullcalendar/core": "^6.1.11",
    "@fullcalendar/daygrid": "^6.1.11",
    "@fullcalendar/interaction": "^6.1.11",
    "@fullcalendar/timegrid": "^6.1.11",
    "@fullcalendar/vue": "^6.1.11",
    "axios": "^1.7.2",
    "pinia": "^2.1.7",
    "vue": "^3.4.29",
    "vue-router": "^4.3.3"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.5",
    "vite": "^5.3.1"
  }
}
```

然後運行：
```bash
docker-compose exec vue_app_dev npm install
```

### 3. 補充業務邏輯
目前控制器（如 `Admin/UserController.php`）和 Vue 視圖（如 `admin/UserManagementView.vue`）是空殼，需實作：
- **後端**：在控制器中加入 CRUD 邏輯，例如：
  ```php
  // laravel/app/Http/Controllers/Admin/UserController.php
  public function store(Request $request)
  {
      $validated = $request->validate([
          'name' => 'required|string|max:255',
          'email' => 'required|email|unique:users',
          'password' => 'required|string|min:8',
          'role' => 'required|in:super_admin,supervisor,caregiver,nutritionist,physiotherapist',
      ]);
      $user = User::create([
          'name' => $validated['name'],
          'email' => $validated['email'],
          'password' => Hash::make($validated['password']),
          'role' => $validated['role'],
      ]);
      return response()->json(['message' => 'User created', 'user' => $user], 201);
  }
  ```
- **前端**：在視圖中加入 API 請求，例如：
  ```vue
  <!-- vue_app/src/views/admin/UserManagementView.vue -->
  <template>
    <div>
      <h2>用戶管理</h2>
      <form @submit.prevent="createUser">
        <input v-model="form.name" placeholder="姓名" required />
        <input v-model="form.email" type="email" placeholder="電子郵件" required />
        <input v-model="form.password" type="password" placeholder="密碼" required />
        <select v-model="form.role">
          <option value="super_admin">超級管理員</option>
          <option value="supervisor">督導</option>
          <option value="caregiver">照護人員</option>
        </select>
        <button type="submit">新增用戶</button>
      </form>
    </div>
  </template>

  <script setup>
  import { reactive } from 'vue';
  import axios from 'axios';

  const form = reactive({
    name: '',
    email: '',
    password: '',
    role: 'caregiver',
  });

  async function createUser() {
    try {
      await axios.post('/api/admin/users', form);
      alert('用戶新增成功');
    } catch (error) {
      alert('新增失敗：' + (error.response?.data?.message || '未知錯誤'));
    }
  }
  </script>
  ```

## 常見問題與解答

以下是一些開發過程中可能遇到的問題和解決方法，幫你快速上手或排查問題。

### Q1: 如何處理 Laravel Sanctum 的 CSRF 保護？
**答**：Sanctum 需要先獲取 CSRF cookie 才能發送 POST 請求（如登入）。在前端，使用 `axios.get('/sanctum/csrf-cookie')` 獲取 cookie，然後再發送請求。後端已配置 `EnsureFrontendRequestsAreStateful` 中介軟體，確保跨域請求正確處理。如果遇到 401 或 419 錯誤，檢查：
- `laravel/.env` 中的 `SESSION_DOMAIN` 和 `SANCTUM_STATEFUL_DOMAINS` 是否設為 `localhost`。
- 前端的 `vite.config.js` 是否正確代理 `/api` 請求到 Nginx。
- 範例：`vue_app/src/stores/auth.js` 中的 `login` 方法已實現 CSRF 處理。

### Q2: 為什麼 API 請求返回 404？
**答**：這通常是路由或 Nginx 配置問題。排查步驟：
- 確認 `laravel/routes/api.php` 中有正確的路由定義（例如 `/api/login`）。
- 檢查 `nginx/default.conf` 是否將 `/api/` 請求轉發到 `laravel_app:9000`。
- 運行 `docker-compose logs nginx` 查看是否有錯誤。
- 解決方法：重啟 Nginx（`docker-compose restart nginx`）或確認 Laravel 容器正常（`docker-compose ps`）。

### Q3: Vue 頁面空白或無法加載？
**答**：可能是 Vite 伺服器問題或代理配置錯誤。檢查：
- `vue_app/vite.config.js` 中的代理設定，確保 `/api` 指向 `http://nginx`。
- 確認 `docker-compose.yml` 中的 `vue_app_dev` 服務是否運行（端口 5173）。
- 運行 `docker-compose logs vue_app_dev` 查看 Vite 日誌。
- 解決方法：重啟 Vue 容器（`docker-compose restart vue_app_dev`）或重新安裝依賴（`npm install`）。

### Q4: 如何在 Laravel 中實現角色權限控制？
**答**：專案使用 Laravel 的 Policy 和中介軟體實現角色權限。例如：
- `app/Policies/UserPolicy.php` 定義了 `isSuperAdmin`、`isSupervisor` 等方法。
- 路由中介軟體（`can:isSuperAdmin`）在 `routes/api.php` 中限制訪問。
- 範例：超級管理員才能訪問 `/api/admin/users`，由以下程式碼控制：
  ```php
  Route::middleware('can:isSuperAdmin')->prefix('admin')->group(function () {
      Route::apiResource('users', \App\Http\Controllers\Admin\UserController::class);
  });
  ```
- 如果需要新增權限，修改 `UserPolicy.php` 或定義新 Policy。

### Q5: 如何讓 Vue 前端動態顯示後端數據？
**答**：使用 Axios 從後端 API 獲取數據，結合 Vue 的響應式系統。例如，顯示用戶列表：
```vue
<!-- vue_app/src/views/admin/UserManagementView.vue -->
<template>
  <div>
    <h2>用戶列表</h2>
    <ul>
      <li v-for="user in users" :key="user.id">{{ user.name }} ({{ user.role }})</li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const users = ref([]);

onMounted(async () => {
  try {
    const response = await axios.get('/api/admin/users');
    users.value = response.data.data;
  } catch (error) {
    console.error('獲取用戶失敗：', error);
  }
});
</script>
```
- 確保後端的 `UserController::index` 返回用戶數據：
```php
public function index()
{
    return response()->json(['data' => User::all()]);
}
```

### Q6: 如何實現薪資計算邏輯？
**答**：薪資計算需結合 `clock_records`、`assignments` 和 `compensation_rules` 表。範例邏輯：
```php
// laravel/app/Http/Controllers/Caregiver/PayrollController.php
public function calculatePayroll(Request $request, $userId)
{
    $records = ClockRecord::where('user_id', $userId)
        ->whereBetween('clock_in', [$request->start_date, $request->end_date])
        ->get();
    $totalHours = $records->sum(fn($record) => $record->clock_out->diffInHours($record->clock_in));
    $rule = CompensationRule::where('role', User::find($userId)->role)->first();
    $amount = $totalHours * $rule->hourly_rate;

    $payroll = Payroll::create([
        'user_id' => $userId,
        'total_hours' => $totalHours,
        'amount' => $amount,
        'period_start' => $request->start_date,
        'period_end' => $request->end_date,
    ]);

    return response()->json(['payroll' => $payroll]);
}
```
- 前端需發送請求並顯示結果，例如：
```vue
<!-- vue_app/src/views/caregiver/PayrollView.vue -->
<template>
  <div>
    <h2>薪資查詢</h2>
    <input v-model="startDate" type="date" />
    <input v-model="endDate" type="date" />
    <button @click="fetchPayroll">查詢</button>
    <p v-if="payroll">總時數：{{ payroll.total_hours }} 小時，金額：{{ payroll.amount }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const startDate = ref('');
const endDate = ref('');
const payroll = ref(null);

async function fetchPayroll() {
  try {
    const response = await axios.get(`/api/caregiver/payroll/${authStore.user.id}`, {
      params: { start_date: startDate.value, end_date: endDate.value },
    });
    payroll.value = response.data.payroll;
  } catch (error) {
    console.error('查詢失敗：', error);
  }
}
</script>
```

### Q7: Docker 容器如何優化效能？
**答**：目前的 `docker-compose.yml` 適合開發，但可做以下優化：
- 使用多階段構建（multi-stage build）減少 Laravel 和 Vue 的映像大小。
- 為 MySQL 添加持久化卷配置（已包含 `db_data`）。
- 生產環境改用 `vue_app/Dockerfile`（而非 `Dockerfile.dev`）生成靜態檔案：
```dockerfile
# vue_app/Dockerfile
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx/default.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```
- 更新 `docker-compose.yml`：
```yaml
vue_app:
  build:
    context: ./vue_app
    dockerfile: Dockerfile
  volumes:
    - ./vue_app/dist:/usr/share/nginx/html
  depends_on:
    - nginx
  networks:
    - care-network
```

## 問題排查

- **無法下載倉庫**：確認網路連線，或檢查倉庫 URL 是否正確（`https://github.com/BpsEason/care_dispatch_platform.git`）。
- **容器啟動失敗**：檢查 Docker 是否運行，確認端口（80, 3306, 5173, 9000）未被佔用。
- **API 請求 404**：確認 `nginx/default.conf` 和 `laravel/routes/api.php` 是否正確，或重啟 Nginx 和 Laravel 容器。
- **Vue 頁面空白**：檢查 `vite.config.js` 的代理設定，或運行 `docker-compose restart vue_app_dev`。
- **資料庫連線錯誤**：確認 `laravel/.env` 的 `DB_HOST=mysql_db`，檢查 MySQL 容器狀態（`docker-compose logs mysql_db`）。

## 待辦事項

專案目前是骨架，功能需手動實作：
1. **實作控制器邏輯**：補充 CRUD 操作，例如用戶管理、個案管理和薪資計算。
2. **完善前端視圖**：實現與後端 API 的串接，顯示數據並處理表單。
3. **生產環境配置**：編譯 Vue 靜態檔案（`npm run build`），調整 Nginx 指向 `vue_app/dist`。
4. **自動化功能**：用 Laravel 的 `schedule` 實現薪資單自動生成。
5. **測試**：加入 PHPUnit（後端）和 Vitest（前端）測試。

## 結語

這專案的架構簡單穩固，用 Laravel 和 Vue.js 搭出來的骨架很適合快速開發。雖然現在倉庫內容不完整，但跑一次腳本就能生成所有檔案，補上依賴和邏輯後就可以用。如果有問題，直接開 issue 或聯繫我，會幫忙看看。
