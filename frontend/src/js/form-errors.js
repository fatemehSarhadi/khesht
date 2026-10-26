// Turns an API error into a message the visitor can act on (and logs details for developers).
export function formErrorMessage(error) {
  console.error("[khesht] form submit failed:", error, error?.payload);
  const code = error?.payload?.code;
  const status = Number(/ (\d{3})$/.exec(error?.message || "")?.[1]) || 0;
  if (code === "khesht_invalid_phone") return "شماره تماس معتبر نیست. نمونه: 09123456789";
  if (code === "khesht_validation") return "لطفاً همه‌ی فیلدها را پر کنید.";
  if (status === 429 || code === "khesht_rate_limited") return "تعداد تلاش‌ها زیاد بود؛ چند دقیقه بعد دوباره امتحان کنید.";
  if (!status) return "ارتباط با سرور برقرار نشد. لطفاً دوباره تلاش کنید.";
  return "ثبت درخواست انجام نشد. لطفاً دوباره تلاش کنید یا با ما تماس بگیرید.";
}

// Messages for account / review actions.
export function accountErrorMessage(error) {
  console.error("[khesht] account action failed:", error, error?.payload);
  const code = error?.payload?.code;
  const status = Number(/ (\d{3})$/.exec(error?.message || "")?.[1]) || 0;
  if (code === "khesht_exists") return "این شماره قبلاً ثبت‌نام کرده است. وارد شوید یا از «فراموشی رمز» استفاده کنید.";
  if (code === "khesht_bad_login") return "شماره یا رمز اشتباه است.";
  if (code === "khesht_locked") return "به‌دلیل چند تلاش ناموفق، ۱۵ دقیقه بعد دوباره امتحان کنید.";
  if (code === "khesht_invalid_phone") return "شماره موبایل معتبر نیست. نمونه: 09123456789";
  if (code === "khesht_validation") return "اطلاعات را کامل وارد کنید (نظر حداقل ۱۰ حرف).";
  if (status === 401) return "نشست شما منقضی شده؛ دوباره وارد شوید.";
  if (status === 429 || code === "khesht_rate_limited") return "تعداد تلاش‌ها زیاد بود؛ کمی بعد دوباره امتحان کنید.";
  if (!status) return "ارتباط با سرور برقرار نشد. لطفاً دوباره تلاش کنید.";
  return "انجام نشد. لطفاً دوباره تلاش کنید.";
}
