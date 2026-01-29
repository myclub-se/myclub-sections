import {getCalendarLocale, getFullCalendarOptions, setupEvents, showDialog} from "../shared/calendar-functions";

document.addEventListener('DOMContentLoaded', () => {
    const smallScreen = document.documentElement.clientWidth < 960;
    const calendarEl = document.getElementById('calendar-div');
    const labels = JSON.parse(calendarEl.dataset.labels);
    const firstDayOfWeek = calendarEl.dataset.firstDayOfWeek;

    const calendar = new FullCalendar.Calendar(calendarEl, getFullCalendarOptions({
        labels,
        events: setupEvents(JSON.parse(calendarEl.dataset.events)),
        locale: getCalendarLocale(calendarEl.dataset.locale),
        firstDay: firstDayOfWeek,
        smallScreen,
        desktopViews: calendarEl.dataset.calendarDesktop,
        desktopDefault: calendarEl.dataset.calendarDesktopDefault,
        mobileViews: calendarEl.dataset.calendarMobile,
        mobileDefault: calendarEl.dataset.calendarMobileDefault,
        showWeekNumbers: calendarEl.dataset.calendarWeekNumbers === '1',
        plugins: [],
        showEvent: (arg) => {
            const item = arg.event;
            const modal = document.getElementById("calendar-modal");

            if (modal) {
                showDialog(item, modal, labels);
            }
        }
    }));

    calendar.render();
});