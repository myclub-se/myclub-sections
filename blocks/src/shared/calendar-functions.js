import svLocale from './sv';
import enLocale from '@fullcalendar/core/locales/en-gb';

/**
 * Get the appropriate FullCalendar locale configuration
 * @param {string} locale
 * @returns Locale configuration object
 */
export const getCalendarLocale = (locale) => {
    return locale === 'sv_SE' ? svLocale : enLocale;
};

const getColorClass = (baseType) => {
    switch (baseType) {
        case 'match':
            return 'red';
        case 'training':
            return 'green';
        case 'meeting':
            return 'blue';
        default:
            return 'yellow';
    }
}

const subtractMinutes = (time, minutes) => {
    let parts = time.split(':');
    let date = new Date();
    date.setHours(parts[0]);
    date.setMinutes(parts[1]);
    date.setSeconds(parts[2]);

    date.setMinutes(date.getMinutes() - minutes);

    let hrs = ("0" + date.getHours()).slice(-2);
    let mins = ("0" + date.getMinutes()).slice(-2);
    let secs = ("0" + date.getSeconds()).slice(-2);

    return `${hrs}:${mins}:${secs}`;
}

/**
 * Get FullCalendar configuration options
 */
export const getFullCalendarOptions = ({labels, events, locale, firstDay, smallScreen, plugins, showEvent}) => {
    const rightToolbar = smallScreen ? 'timeGridDay,listMonth' : 'dayGridMonth,timeGridWeek,listMonth';
    const initialView = smallScreen ? 'listMonth' : 'dayGridMonth';

    return {
        allDaySlot: false,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: rightToolbar,
        },
        locale,
        events,
        firstDay,
        timeZone: 'Europe/Stockholm',
        weekNumbers: true,
        weekText: labels.weekText,
        weekTextLong: labels.weekTextLong,
        initialView,
        plugins,
        eventClick: (arg) => showEvent(arg),
        eventContent: (arg) => {
            const item = arg.event;
            const element = document.createElement('div');
            let timeText = arg.timeText;
            element.classList.add('fc-event-title');
            element.classList.add(getColorClass(item.extendedProps.base_type));

            if (item.extendedProps.meetUpTime && item.extendedProps.meetUpTime !== item.extendedProps.startTime) {
                if (!timeText) {
                    timeText = item.extendedProps.startTime.substring(0, 5);
                }

                timeText += ` (${item.extendedProps.meetUpTime.substring(0, 5)})`;
            }

            element.innerHTML = '<div class="myclub-sections-event-time">' +
                timeText + '</div><div class="myclub-sections-event-title">' +
                item.title.replaceAll('u0022', '\"') +
                '</div>';

            let arrayOfDomNodes = [
                element
            ];

            return {domNodes: arrayOfDomNodes};
        },
    };
};

export const setupEvents = (actvities) => {
    return actvities.map((activity) => {
        let backgroundColor = '#9e8c39';
        let meetUpTime = parseInt(activity.meet_up_time);
        let meetUpTimeString = activity.start_time;

        if (meetUpTime) {
            meetUpTimeString = subtractMinutes(activity.start_time, meetUpTime);
        }

        switch (activity.base_type) {
            case 'match':
                backgroundColor = '#c1272d'; // Match color
                break;
            case 'training':
                backgroundColor = '#009245'; // Training color
                break;
            case 'meeting':
                backgroundColor = '#396b9e'; // Meeting color
                break;
            default:
                break;
        }

        return {
            title: activity.title.replaceAll('u0022', '\"'),
            start: `${activity.day} ${activity.start_time}`,
            end: `${activity.day} ${activity.end_time}`,
            backgroundColor,
            borderColor: backgroundColor,
            color: '#fff',
            display: 'block',
            extendedProps: {
                base_type: activity.base_type,
                calendar_name: activity.calendar_name,
                location: activity.location,
                description: activity.description.replaceAll('<br /><br />', '<br />').replaceAll('u0022', '\"'),
                endTime: activity.end_time,
                startTime: activity.start_time,
                meetUpPlace: activity.meet_up_place,
                meetUpTime: meetUpTimeString,
                type: activity.type,
            },
        };
    });
}

export const showDialog = (item, modal, labels) => {
    const {
        type,
        calendar_name,
        startTime,
        endTime,
        location,
        meetUpTime,
        meetUpPlace,
        description
    } = item.extendedProps;
    const content = modal?.querySelector('.modal-body');
    const close = modal?.querySelector('.close');

    let output = `<div class="name">${type}</div>`;
    output += '<table>';
    output += `<tr><th>${labels.calendar}</th><td>${calendar_name}</td></tr>`;
    output += `<tr><th>${labels.name}</th><td>${item.title}</td></tr>`;
    output += `<tr><th>${labels.when}</th><td>${startTime.substring(0, 5)} - ${endTime.substring(0, 5)}</td></tr>`;
    output += `<tr><th>${labels.location}</th><td>${location}</td></tr>`;
    if (meetUpTime && meetUpTime !== startTime) {
        output += `<tr><th>${labels.meetUpTime}</th><td>${meetUpTime.substring(0, 5)}</td></tr>`;
    }
    if (meetUpPlace) {
        output += `<tr><th>${labels.meetUpLocation}</th><td>${meetUpPlace}</td></tr>`;
    }
    if (description) {
        output += `<tr><th>${labels.description}</th><td>${description}</td></tr>`;
    }
    output += '</table>';

    content.innerHTML = output;

    modal.classList.add('modal-open');
    const closeModal = () => {
        modal.classList.remove('modal-open');
        close?.removeEventListener('click', closeModal);
    };
    close?.addEventListener('click', closeModal);
    modal.addEventListener('click', closeModal);
}
