import {useEffect, useRef, useState, useCallback} from '@wordpress/element';
import {InspectorControls, useBlockProps} from '@wordpress/block-editor';
import {PanelBody, PanelRow, SelectControl} from '@wordpress/components';
import './editor.scss';
import {__} from "@wordpress/i18n";

import {getMyClubSections} from "../shared/edit-functions";
import {Calendar} from "@fullcalendar/core";
import {getCalendarLocale, getFullCalendarOptions, setupEvents, showDialog} from "../shared/calendar-functions";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";

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

/**
 * Pre-inject a <style data-fullcalendar> element so that FullCalendar's
 * ensureElHasStyles() finds it via querySelector instead of trying to
 * insertBefore the DOCTYPE node in the block-editor iframe.
 */
function ensureStyleElement(el) {
    if (!el || !el.isConnected) return;

    const rootNode = el.getRootNode();
    if (!rootNode || rootNode.querySelector('style[data-fullcalendar]')) return;

    const styleEl = document.createElement('style');
    styleEl.setAttribute('data-fullcalendar', '');

    const head = rootNode === document
        ? document.head
        : (rootNode.head || rootNode.querySelector('head'));

    if (head) {
        head.appendChild(styleEl);
    }
}

export default function Edit({attributes, setAttributes}) {
    const [calendarTitle, setCalendarTitle] = useState('');
    const [calendarDesktopViews, setCalendarDesktopViews] = useState('');
    const [calendarDesktopViewsDefault, setCalendarDesktopViewsDefault] = useState('');
    const [calendarMobileViews, setCalendarMobileViews] = useState('');
    const [calendarMobileViewsDefault, setCalendarMobileViewsDefault] = useState('');
    const [calendarShowWeekNumbers, setCalendarShowWeekNumbers] = useState(true);
    const [noEventsContent, setNoEventsContent] = useState('');
    const [postEvents, setPostEvents] = useState({events: [], loaded: false});
    const [optionsLoaded, setOptionsLoaded] = useState(false);
    const [posts, setPosts] = useState([]);
    const {apiFetch} = wp;
    const {useSelect} = wp.data;
    let calendarRef = useRef(null);
    let calendarElRef = useRef();
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
            if (calendarRef.current) {
                calendarRef.current.setOption('firstDay', startOfWeek);
            }
            return startOfWeek;
        }

        return 1;
    });
    const selectPostLabel = {
        label: __('Select a section', 'myclub-sections'),
        value: ''
    };
    const handleShowEvent = useCallback((arg) => {
        const item = arg.event;
        const modal = modalRef?.current;

        if (modal) {
            showDialog(item, modal, labels);
        }
    }, []);
    const resetPostEvents = (loaded = false) => {
        setPostEvents({
            events: [],
            loaded,
        });
    };

    const fetchEvents = async (post_id) => {
        try {
            const post = await apiFetch({path: `/myclub/sections/v1/sections/${post_id}`});
            const allActivities = JSON.parse(post.activities);
            const events = setupEvents(allActivities);

            setPostEvents({
                events,
                loaded: true,
            });
        } catch (error) {
            throw new Error(error.message);
        }
    };

    // Create/destroy the calendar instance
    useEffect(() => {
        const el = calendarElRef.current;
        if (!el || !optionsLoaded) return;

        ensureStyleElement(el);

        const options = getFullCalendarOptions({
            labels,
            events: postEvents.events || [],
            firstDay: startOfWeek,
            locale: getCalendarLocale(currentLocale),
            smallScreen: window.innerWidth < 960,
            desktopViews: calendarDesktopViews,
            desktopDefault: calendarDesktopViewsDefault,
            mobileViews: calendarMobileViews,
            mobileDefault: calendarMobileViewsDefault,
            showWeekNumbers: calendarShowWeekNumbers,
            plugins: [dayGridPlugin, timeGridPlugin, listPlugin],
            showEvent: (arg) => handleShowEvent(arg),
            noEventsContent: noEventsContent,
        });

        const calendar = new Calendar(el, options);
        calendar.render();
        calendarRef.current = calendar;

        return () => {
            calendar.destroy();
            calendarRef.current = null;
        };
    }, [calendarDesktopViews, calendarDesktopViewsDefault, calendarMobileViews, calendarMobileViewsDefault, calendarShowWeekNumbers, postEvents.events, startOfWeek, currentLocale, optionsLoaded]);

    useEffect(() => {
        apiFetch({path: '/myclub/sections/v1/options'}).then(options => {
            setCalendarTitle(options.myclub_sections_calendar_title);
            setCalendarDesktopViews(options.myclub_sections_section_calendar_desktop_views);
            setCalendarDesktopViewsDefault(options.myclub_sections_section_calendar_desktop_views_default);
            setCalendarMobileViews(options.myclub_sections_section_calendar_mobile_views);
            setCalendarMobileViewsDefault(options.myclub_sections_section_calendar_mobile_views_default);
            setCalendarShowWeekNumbers(options.myclub_sections_section_calendar_show_week_numbers);
            setNoEventsContent(options.myclub_sections_no_activities_message);
            setOptionsLoaded(true);
        });

        getMyClubSections(setPosts, selectPostLabel);
    }, []);

    useEffect(() => {
        resetPostEvents();

        if (attributes.post_id) {
            fetchEvents(attributes.post_id).catch(error => {
                console.error('Error fetching events:', error);
                setPostEvents({
                    events: [],
                    loaded: true,
                });
            });
        } else {
            resetPostEvents(true);
        }
    }, [attributes.post_id]);

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Content settings', 'myclub-sections')}>
                    <PanelRow>
                        <SelectControl
                            label={__('Section', 'myclub-sections')}
                            value={attributes.post_id}
                            options={posts}
                            onChange={(value) => {
                                setAttributes({post_id: value});
                            }}
                        />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <div {...useBlockProps()}>
                <div className="myclub-sections-calendar" ref={outerRef}>
                    <div className="myclub-sections-calendar-container">
                        <h3 className="myclub-sections-header">{calendarTitle}</h3>
                        {optionsLoaded ? (
                            <div id="calendar-div" ref={ calendarElRef } />
                        ) : (
                            <p>{__('Loading...', 'myclub-sections')}</p>
                        )}
                    </div>
                    <div className="calendar-modal" ref={modalRef}>
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