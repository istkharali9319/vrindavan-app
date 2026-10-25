/* Check-in / check-out calendars for every form with check_in and check_out fields */
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";

const sameDay = (a, b) => a && b && a.toDateString() === b.toDateString();
const addDays = (date, days) => {
  const copy = new Date(date);
  copy.setDate(copy.getDate() + days);
  return copy;
};

document.querySelectorAll("form").forEach((form) => {
  const checkInField = form.querySelector('input[name="check_in"]:not([type="hidden"])');
  const checkOutField = form.querySelector('input[name="check_out"]:not([type="hidden"])');
  if (!checkInField || !checkOutField) return;

  let checkIn = null;
  let checkOut = null;

  // Shade the nights between check-in and check-out in both calendars
  const markStay = (_dates, _str, _picker, day) => {
    if (!checkIn || !checkOut) return;
    const start = checkIn.selectedDates[0];
    const end = checkOut.selectedDates[0];
    if (sameDay(day.dateObj, start)) day.classList.add("stay-start");
    if (sameDay(day.dateObj, end)) day.classList.add("stay-end");
    if (start && end && day.dateObj > start && day.dateObj < end) day.classList.add("inRange");
  };

  const redrawBoth = () => [checkIn, checkOut].forEach((picker) => picker && picker.redraw());

  const options = (field, extra) => ({
    altInput: true,
    altFormat: form.classList.contains("booking-bar") ? "j M Y" : "D, j M Y",
    dateFormat: "Y-m-d",
    disableMobile: true,
    monthSelectorType: "static",
    position: "below left",
    showMonths: 1,
    locale: { firstDayOfWeek: 1 },
    maxDate: field.max || null,
    onDayCreate: markStay,
    onReady: (_d, _s, picker) => {
      picker.calendarContainer.classList.add("stay-calendar");
      picker.altInput.required = field.required;
      picker.altInput.placeholder = "Select date";
      picker.altInput.id = field.id;
      field.removeAttribute("id");
    },
    ...extra,
  });

  checkIn = flatpickr(checkInField, options(checkInField, {
    minDate: checkInField.min || "today",
    onChange: ([date]) => {
      if (!date) return;
      const earliestOut = addDays(date, 1);
      checkOut.set("minDate", earliestOut);
      if (!checkOut.selectedDates[0] || checkOut.selectedDates[0] <= date) {
        checkOut.clear();
      }
      redrawBoth();
      setTimeout(() => checkOut.open(), 0);
    },
  }));

  const chosenIn = checkIn.selectedDates[0];
  checkOut = flatpickr(checkOutField, options(checkOutField, {
    minDate: chosenIn ? addDays(chosenIn, 1) : checkOutField.min || addDays(new Date(), 1),
    onChange: redrawBoth,
  }));

  redrawBoth();
});
