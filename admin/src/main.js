import { createApp } from "vue";
import { createPinia } from "pinia";
import { setupCalendar } from "v-calendar";
import App from "./App.vue";
import router from "./router";
import "./assets/main.css";
import "v-calendar/style.css";

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(setupCalendar, {});

app.mount("#app");
