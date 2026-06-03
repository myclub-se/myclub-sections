import {getCalendarLocale, getFullCalendarOptions, setupEvents, showDialog} from "../shared/calendar-functions";

document.addEventListener('DOMContentLoaded', () => {
    const smallScreen = document.documentElement.clientWidth < 960;
    const calendarEl = document.getElementById('club-calendar-div');
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
        height: calendarEl.dataset.calendarHeight || undefined,
        showEvent: (arg) => {
            const item = arg.event;
            const modal = document.getElementById("club-calendar-modal");

            if (modal) {
                showDialog(item, modal, labels);
            }
        },
        noEventsContent: calendarEl.dataset.noEventsContent,
    }));

    calendar.render();

    const subscribeBtn = document.querySelector('.myclub-sections-subscribe-button');
    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', () => {
            const modalId = subscribeBtn.dataset.subscribeModal;
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('modal-open');
                const closeModal = () => {
                    modal.classList.remove('modal-open');
                    modal.querySelectorAll('.close').forEach(el => el.removeEventListener('click', closeModal));
                    modal.removeEventListener('click', handleBackdropClick);
                };
                const handleBackdropClick = (event) => {
                    if (event.target === modal) closeModal();
                };
                modal.querySelectorAll('.close').forEach(el => el.addEventListener('click', closeModal));
                modal.addEventListener('click', handleBackdropClick);
            }
        });
    }
});