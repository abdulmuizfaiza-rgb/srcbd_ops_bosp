import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

// Diekspos ke window supaya bisa dipakai langsung di blok Alpine.js
// (x-init) pada halaman Blade, misalnya di menu Dashboard.
window.Chart = Chart;
