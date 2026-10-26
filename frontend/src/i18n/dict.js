export const fa = {
  nav: {
    home: "خانه",
    amlak: "املاک",
    bazar: "بازار و تحلیل",
    mag: "مجله",
    tamas: "تماس با ما",
    hamkari: "همکاری",
  },
  consultCta: "درخواست مشاوره",
  consultSuccess:
    "از ثبت درخواست شما بسیار متشکریم، کارشناسان ما در اسرع وقت با شما تماس خواهند گرفت.",
};

export const en = {
  nav: {
    home: "Home",
    amlak: "Real Estate",
    bazar: "Market & Analysis",
    mag: "Magazine",
    tamas: "Contact Us",
    hamkari: "Collaboration",
  },
  consultCta: "Request consultation",
  consultSuccess:
    "Thank you. Our specialists will contact you as soon as possible.",
};

export function t(locale, path) {
  const dict = locale === "en" ? en : fa;
  return path.split(".").reduce((acc, key) => acc?.[key], dict) || path;
}
