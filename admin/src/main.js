import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";
import VueApexCharts from "vue3-apexcharts";
import { setupCalendar, DatePicker } from "v-calendar";
import "v-calendar/style.css";
import "./assets/main.css";

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(setupCalendar, {});
app.component("apexchart", VueApexCharts);
app.component("VDatePicker", DatePicker);

app.mount("#app");
