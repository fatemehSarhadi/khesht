import { submitCollaboration } from "../api/forms.js";
import { formErrorMessage } from "./form-errors.js";

const SUCCESS =
  "از ثبت درخواست شما بسیار متشکریم، کارشناسان ما در اسرع وقت با شما تماس خواهند گرفت.";

export function initCollaborationForm() {
  const form = document.querySelector("[data-collab-form]");
  const confirm = document.querySelector("[data-collab-confirm]");
  if (!form) return;

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const data = new FormData(form);
    if (data.get("website")) return;
    try {
      await submitCollaboration({
        name: data.get("name"),
        phone: data.get("phone"),
        subject: data.get("subject"),
        description: data.get("description"),
      });
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
