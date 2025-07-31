<template>
  <div>
    <h1>Admin Dashboard</h1>
    <p>Welcome, {{ authStore.user?.name }} ({{ authStore.user?.role }})!</p>
    <button @click="authStore.logout">Logout</button>
    <hr>
    <h3>Admin Features:</h3>
    <ul>
        <li><router-link to="/admin/users">User Management</router-link></li>
        <li><router-link to="/admin/compensation-rules">Compensation Rules</router-link></li>
        <li><router-link to="/admin/reports">Reports</router-link></li>
    </ul>
  </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
const authStore = useAuthStore();
</script>
