import {useBlockProps} from '@wordpress/block-editor';
import {useEffect, useMemo, useRef, useState} from '@wordpress/element';
import './editor.scss';
import {__} from "@wordpress/i18n";
import {getCalendarLocale, getFullCalendarOptions, setupEvents, showDialog} from "../shared/calendar-functions";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import FullCalendar from "@fullcalendar/react";

const labels = {
    calendar: __('Calendar', 'myclub-sections'),
    name: __('Name', 'myclub-sections'),
    when: __('When', 'myclub-sections'),
    location: __('Location', 'myclub-sections'),
    meetUpTime: __('Gathering time', 'myclub-sections'),
    meetUpLocation: __('Gathering location', 'myclub-sections'),
    description: __('Description', 'myclub-sections'),
    weekText: __('W', 'myclub-sections'),
    weekTextLong: __('Week', 'myclub-sections'),
};

export default function Edit({attributes, setAttributes}) {
    const [calendarTitle, setCalendarTitle] = useState('');
    const [events, setEvents] = useState([]);
    const {apiFetch} = wp;
    const {useSelect} = wp.data;
    let calendarRef = useRef();
    let outerRef = useRef();
    let modalRef = useRef();
    const currentLocale = useSelect((select) => {
        if (select("core").getSite()) {
            return select('core').getSite().language;
        }

        return 'sv_SE';
    });
    const startOfWeek = useSelect((select) => {
        if (select("core").getSite()) {
            const startOfWeek = select('core').getSite().start_of_week;
            if (calendarRef && calendarRef.current) {
                const api = calendarRef.current.getApi();
                api.setOption('firstDay', startOfWeek);
            }
            return startOfWeek;
        }

        return 1;
    });
    const handleShowEvent = (arg) => {
        const item = arg.event;
        const modal = modalRef?.current;

        if (modal) {
            showDialog(item, modal, labels);
        }
    };
    const options = useMemo(() => getFullCalendarOptions({
        labels,
        events,
        startOfWeek,
        locale: getCalendarLocale(currentLocale),
        smallScreen: window.innerWidth < 960,
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin],
        showEvent: (arg) => handleShowEvent(arg)
    }), [events, startOfWeek, currentLocale]);

    const getClubEvents = () => {
        apiFetch({path: '/myclub/sections/v1/club-activities'}).then(activities => {
            setEvents(setupEvents(activities));
        });
    }

    useEffect(() => {
        apiFetch({path: '/myclub/sections/v1/options'}).then(options => {
            setCalendarTitle(options.myclub_sections_club_calendar_title);
            getClubEvents();
        });
    }, []);

    return (
        <>
            <div {...useBlockProps()}>
                <div className="myclub-sections-club-calendar" ref={outerRef}>
                    <div class="myclub-sections-club-calendar-container">
                        <h3 class="myclub-sections-header">{calendarTitle}</h3>
                        <FullCalendar ref={calendarRef} {...options} />
                    </div>
                    <div className="club-calendar-modal" ref={modalRef}>
                        <div className="modal-content">
                            <span className="close">&times;</span>
                            <div className="modal-body">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}