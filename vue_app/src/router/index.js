import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

import LoginView from '../views/LoginView.vue';
import AdminDashboard from '../views/AdminDashboard.vue';
import SupervisorDashboard from '../views/SupervisorDashboard.vue';
import CaregiverSchedule from '../views/CaregiverSchedule.vue';
import NotFoundView from '../views/NotFoundView.vue';

# Admin Views
import AdminUserManagementView from '../views/admin/UserManagementView.vue';
import AdminCompensationRulesView from '../views/admin/CompensationRulesView.vue';
import AdminReportsView from '../views/admin/ReportsView.vue';

# Supervisor Views
import SupervisorPatientManagementView from '../views/supervisor/PatientManagementView.vue';
import SupervisorCarePlanManagementView from '../views/supervisor/CarePlanManagementView.vue';
import SupervisorAssignmentSchedulingView from '../views/supervisor/AssignmentSchedulingView.vue';
import SupervisorLeaveRequestApprovalView from '../views/supervisor/LeaveRequestApprovalView.vue';
import SupervisorCaregiverPayrollView from '../views/supervisor/CaregiverPayrollView.vue';

# Caregiver Views
import CaregiverAssignmentDetailView from '../views/caregiver/AssignmentDetailView.vue';
import CaregiverLeaveRequestView from '../views/caregiver/LeaveRequestView.vue';
import CaregiverPayrollView from '../views/caregiver/CaregiverPayrollView.vue';


const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/login',
            name: 'Login',
            component: LoginView,
        },
        {
            path: '/',
            name: 'Home',
            redirect: { name: 'Login' }
        },
        # Admin Routes
        {
            path: '/admin/dashboard',
            name: 'AdminDashboard',
            component: AdminDashboard,
            meta: { requiresAuth: true, roles: ['super_admin'] },
        },
        {
            path: '/admin/users',
            name: 'AdminUserManagement',
            component: AdminUserManagementView,
            meta: { requiresAuth: true, roles: ['super_admin'] },
        },
        {
            path: '/admin/compensation-rules',
            name: 'AdminCompensationRules',
            component: AdminCompensationRulesView,
            meta: { requiresAuth: true, roles: ['super_admin'] },
        },
        {
            path: '/admin/reports',
            name: 'AdminReports',
            component: AdminReportsView,
            meta: { requiresAuth: true, roles: ['super_admin'] },
        },

        # Supervisor Routes
        {
            path: '/supervisor/dashboard',
            name: 'SupervisorDashboard',
            component: SupervisorDashboard,
            meta: { requiresAuth: true, roles: ['supervisor'] },
        },
        {
            path: '/supervisor/patients',
            name: 'SupervisorPatientManagement',
            component: SupervisorPatientManagementView,
            meta: { requiresAuth: true, roles: ['supervisor'] },
        },
        {
            path: '/supervisor/care-plans',
            name: 'SupervisorCarePlanManagement',
            component: SupervisorCarePlanManagementView,
            meta: { requiresAuth: true, roles: ['supervisor'] },
        },
        {
            path: '/supervisor/assignments',
            name: 'SupervisorAssignmentScheduling',
            component: SupervisorAssignmentSchedulingView,
            meta: { requiresAuth: true, roles: ['supervisor'] },
        },
        {
            path: '/supervisor/leave-requests',
            name: 'SupervisorLeaveRequestApproval',
            component: SupervisorLeaveRequestApprovalView,
            meta: { requiresAuth: true, roles: ['supervisor'] },
        },
        {
            path: '/supervisor/payrolls',
            name: 'SupervisorCaregiverPayroll',
            component: SupervisorCaregiverPayrollView,
            meta: { requiresAuth: true, roles: ['supervisor'] },
        },

        # Caregiver Routes
        {
            path: '/caregiver/schedule',
            name: 'CaregiverSchedule',
            component: CaregiverSchedule, # 日曆主要視圖
            meta: { requiresAuth: true, roles: ['caregiver', 'nutritionist', 'physiotherapist'] },
        },
        {
            path: '/caregiver/assignments/:id', # 任務詳情
            name: 'CaregiverAssignmentDetail',
            component: CaregiverAssignmentDetailView,
            meta: { requiresAuth: true, roles: ['caregiver', 'nutritionist', 'physiotherapist'] },
        },
        {
            path: '/caregiver/leave-requests',
            name: 'CaregiverLeaveRequest',
            component: CaregiverLeaveRequestView,
            meta: { requiresAuth: true, roles: ['caregiver', 'nutritionist', 'physiotherapist'] },
        },
        {
            path: '/caregiver/payrolls',
            name: 'CaregiverPayroll',
            component: CaregiverPayrollView,
            meta: { requiresAuth: true, roles: ['caregiver', 'nutritionist', 'physiotherapist'] },
        },

        # Not Found Page
        {
            path: '/:pathMatch(.*)*',
            name: 'NotFound',
            component: NotFoundView,
        },
    ],
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    if (!authStore.user && to.meta.requiresAuth && !to.path.includes('/login')) {
        await authStore.checkAuth();
    }

    const requiresAuth = to.meta.requiresAuth;
    const requiredRoles = to.meta.roles;
    const isLoggedIn = authStore.isLoggedIn;
    const userRole = authStore.getUserRole;

    if (requiresAuth && !isLoggedIn) {
        next({ name: 'Login' });
    } else if (requiresAuth && isLoggedIn && requiredRoles && !requiredRoles.includes(userRole)) {
        console.warn(\`User with role "\${userRole}" attempted to access route "\${to.path}" which requires roles: \${requiredRoles.join(', ')}\`);
        # 可以導向一個權限不足的頁面，或者直接回到儀表板
        if (userRole === 'super_admin') next({ name: 'AdminDashboard' });
        else if (userRole === 'supervisor') next({ name: 'SupervisorDashboard' });
        else if (['caregiver', 'nutritionist', 'physiotherapist'].includes(userRole)) next({ name: 'CaregiverSchedule' });
        else next('/'); # 未知角色導向首頁
    } else {
        next();
    }
});

export default router;
