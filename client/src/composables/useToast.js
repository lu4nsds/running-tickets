import { createApp, h } from "vue";
import Toast from "@/components/ui/Toast.vue";

let toastContainer = null;
let toastCount = 0;

const getOrCreateContainer = () => {
    if (!toastContainer) {
        toastContainer = document.createElement("div");
        toastContainer.id = "toast-container";
        document.body.appendChild(toastContainer);
    }
    return toastContainer;
};

const showToast = (options) => {
    const {
        type = "success",
        title = "",
        message = "",
        duration = 5000,
    } = options;

    const container = getOrCreateContainer();
    const toastWrapper = document.createElement("div");
    toastWrapper.id = `toast-${++toastCount}`;
    container.appendChild(toastWrapper);

    const app = createApp({
        render() {
            return h(Toast, {
                type,
                title,
                message,
                duration,
                onClose: () => {
                    app.unmount();
                    if (toastWrapper.parentNode) {
                        toastWrapper.parentNode.removeChild(toastWrapper);
                    }
                },
            });
        },
    });

    app.mount(toastWrapper);
};

export const useToast = () => {
    const success = (message, title = "Sucesso") => {
        showToast({ type: "success", title, message });
    };

    const error = (message, title = "Erro") => {
        showToast({ type: "error", title, message });
    };

    const warning = (message, title = "Atenção") => {
        showToast({ type: "warning", title, message });
    };

    const info = (message, title = "Informação") => {
        showToast({ type: "info", title, message });
    };

    const toast = (options) => {
        showToast(options);
    };

    return {
        success,
        error,
        warning,
        info,
        toast,
    };
};
