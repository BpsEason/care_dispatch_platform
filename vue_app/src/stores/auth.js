import { defineStore } from 'pinia';
import axios from 'axios';
import router from '../router'; # 引入 router

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
        getUserRole: (state) => state.user?.role,
    },
    actions: {
        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                await axios.get(`${API_BASE_URL}/sanctum/csrf-cookie`);
                const response = await axios.post(`${API_BASE_URL}/login`, credentials);
                this.user = response.data.user;
                localStorage.setItem('user', JSON.stringify(this.user));
                # 登入成功後根據角色導向不同儀表板
                if (this.isSuperAdmin) {
                    router.push({ name: 'AdminDashboard' });
                } else if (this.isSupervisor) {
                    router.push({ name: 'SupervisorDashboard' });
                } else if (this.isCaregiver) {
                    router.push({ name: 'CaregiverSchedule' });
                } else {
                    router.push('/');
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'Login failed.';
                console.error('Login error:', error);
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
                this.error = error.response?.data?.message || 'Logout failed.';
                console.error('Logout error:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async checkAuth() {
            if (!this.user) {
                this.loading = true;
                try {
                    const response = await axios.get(`${API_BASE_URL}/user`);
                    this.user = response.data;
                    localStorage.setItem('user', JSON.stringify(this.user)); # 確保重設localStorage
                } catch (error) {
                    this.user = null;
                    localStorage.removeItem('user');
                } finally {
                    this.loading = false;
                }
            }
        }
    },
});
