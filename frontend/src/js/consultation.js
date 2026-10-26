import { submitConsultation } from "../api/forms.js";
import { formErrorMessage } from "./form-errors.js";

const SUCCESS =
  "از ثبت درخواست شما بسیار متشکریم، کارشناسان ما در اسرع وقت با شما تماس خواهند گرفت.";

function fieldsFromForm(form) {
  const data = new FormData(form);
  return {
    name: data.get("name"),
    phone: data.get("phone"),
    subject: data.get("subject"),
    description: data.get("description"),
    website: data.get("website"),
  };
}

export function initConsultation() {
  const openers = document.querySelectorAll("[data-open-consult]");
  const modal = document.querySelector("[data-consult-modal]");
  const form = document.querySelector("[data-consult-form]");
  const confirm = document.querySelector("[data-consult-confirm]");
  if (!modal || !form) return;

  const close = () => {
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
  };

  openers.forEach((btn) =>
    btn.addEventListener("click", () => {
      modal.classList.add("is-open");
      modal.setAttribute("aria-hidden", "false");
    })
  );

  modal.addEventListener("click", (event) => {
    if (event.target === modal || event.target.matches("[data-close-modal]")) {
      close();
    }
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const payload = fieldsFromForm(form);
    if (payload.website) return;
    try {
      await submitConsultation(payload);
      form.hidden = true;
      if (confirm) {
        confirm.textContent = SUCCESS;
        confirm.classList.add("is-visible");
      }
    } catch (error) {
      form.querySelector("[data-form-error]").textContent = formErrorMessage(error);
    }
  });
}
