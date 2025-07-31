<template>
  <div>
    <h1>Supervisor Dashboard</h1>
    <p>Welcome, {{ authStore.user?.name }} ({{ authStore.user?.role }})!</p>
    <button @click="authStore.logout">Logout</button>
    <hr>
    <h3>Supervisor Features:</h3>
    <ul>
        <li><router-link to="/supervisor/patients">Patient Management</router-link></li>
        <li><router-link to="/supervisor/care-plans">Care Plan Management</router-link></li>
        <li><router-link to="/supervisor/assignments">Assignment & Scheduling</router-link></li>
        <li><router-link to="/supervisor/leave-requests">Leave Request Approval</router-link></li>
        <li><router-link to="/supervisor/payrolls">Caregiver Payrolls</router-link></li>
    </ul>
  </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
const authStore = useAuthStore();
</script>
