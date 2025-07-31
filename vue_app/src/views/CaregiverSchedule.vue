<template>
  <div>
    <h1>Caregiver Schedule</h1>
    <p>Welcome, {{ authStore.user?.name }} ({{ authStore.user?.role }})!</p>
    <button @click="authStore.logout">Logout</button>
    <hr>
    <h3>Caregiver Features:</h3>
    <ul>
        <li><router-link to="/caregiver/leave-requests">Submit Leave Request</router-link></li>
        <li><router-link to="/caregiver/payrolls">My Payrolls</router-link></li>
    </ul>
    <div id='calendar'></div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
import { onMounted } from 'vue';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

const authStore = useAuthStore();

onMounted(() => {
  const calendarEl = document.getElementById('calendar');
  const calendar = new Calendar(calendarEl, {
    plugins: [ dayGridPlugin, timeGridPlugin, interactionPlugin ],
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    events: [
      # 這裡將是從後端 API 獲取的排班數據
      { title: '個案A - 身體照顧', start: '2025-08-01T09:00:00', end: '2025-08-01T10:00:00' },
      { title: '個案B - 陪同就醫', start: '2025-08-02T14:00:00', end: '2025-08-02T16:00:00' }
    ]
  });
  calendar.render();
});
</script>
