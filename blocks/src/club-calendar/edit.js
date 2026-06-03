import {InspectorControls, useBlockProps} from '@wordpress/block-editor';
import {Icon, PanelBody, PanelRow, TextControl, ToggleControl} from '@wordpress/components';
import {calendar} from '@wordpress/icons';
import {useEffect, useRef, useState, useCallback} from '@wordpress/element';
import './editor.scss';
import {__} from "@wordpress/i18n";
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

const calendarPlugins = [dayGridPlugin, timeGridPlugin, listPlugin];

// Pre-inject a <style data-fullcalendar> element so that FullCalendar's
// ensureElHasStyles() finds it via querySelector instead of trying to
// insertBefore the DOCTYPE node in the block-editor iframe.
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
    const [optionsLoaded, setOptionsLoaded] = useState(false);
    const [events, setEvents] = useState([]);
    const [calendarHeight, setCalendarHeight] = useState('');
    const [clubCalendarUrl, setClubCalendarUrl] = useState('');
    const [subscribeModalOpen, setSubscribeModalOpen] = useState(false);
    const {apiFetch} = wp;
    const {useSelect} = wp.data;
    const calendarRef = useRef(null);
    const calendarElRef = useRef();
    const modalRef = useRef();
    const currentLocale = useSelect((select) => {
        if (select("core").getSite()) {
            return select('core').getSite().language;
        }

        return 'sv_SE';
    });
    const startOfWeek = useSelect((select) => {
        if (select("core").getSite()) {
            return select('core').getSite().start_of_week;
        }

        return 1;
    });
    const handleShowEvent = useCallback((arg) => {
        const item = arg.event;
        const modal = modalRef?.current;

        if (modal) {
            showDialog(item, modal, labels);
        }
    }, []);

    const getClubEvents = () => {
        apiFetch({path: '/myclub/sections/v1/club-activities'}).then(activities => {
            setEvents(setupEvents(activities));
        }).catch(error => {
            console.error('Error fetching club activities:', error);
        });
    }

    useEffect(() => {
        if (calendarRef.current) {
            calendarRef.current.setOption('firstDay', startOfWeek);
        }
    }, [startOfWeek]);

    // Create/destroy the calendar instance
    useEffect(() => {
        const el = calendarElRef.current;
        if (!el || !optionsLoaded) return;

        ensureStyleElement(el);

        const options = getFullCalendarOptions({
            labels,
            events,
            firstDay: startOfWeek,
            locale: getCalendarLocale(currentLocale),
            smallScreen: window.innerWidth < 960,
            desktopViews: calendarDesktopViews,
            desktopDefault: calendarDesktopViewsDefault,
            mobileViews: calendarMobileViews,
            mobileDefault: calendarMobileViewsDefault,
            showWeekNumbers: calendarShowWeekNumbers,
            plugins: calendarPlugins,
            showEvent: (arg) => handleShowEvent(arg),
            noEventsContent: noEventsContent,
            height: attributes.height || calendarHeight || undefined,
        });

        const calendar = new Calendar(el, options);
        calendar.render();
        calendarRef.current = calendar;

        return () => {
            calendar.destroy();
            calendarRef.current = null;
        };
    }, [calendarDesktopViews, calendarDesktopViewsDefault, calendarMobileViews, calendarMobileViewsDefault, calendarShowWeekNumbers, events, startOfWeek, currentLocale, optionsLoaded, calendarHeight, attributes.height]);

    useEffect(() => {
        apiFetch({path: '/myclub/sections/v1/options'}).then(options => {
            setCalendarTitle(options.myclub_sections_club_calendar_title);
            setCalendarDesktopViews(options.myclub_sections_club_calendar_desktop_views);
            setCalendarDesktopViewsDefault(options.myclub_sections_club_calendar_desktop_views_default);
            setCalendarMobileViews(options.myclub_sections_club_calendar_mobile_views);
            setCalendarMobileViewsDefault(options.myclub_sections_club_calendar_mobile_views_default);
            setCalendarShowWeekNumbers(options.myclub_sections_club_calendar_show_week_numbers === '1');
            setNoEventsContent(options.myclub_sections_no_activities_message);
            setClubCalendarUrl(options.myclub_sections_club_calendar_url || '');
            setCalendarHeight(options.myclub_sections_club_calendar_height || '');
            setOptionsLoaded(true);
            getClubEvents();
        });
    }, []);

    const webcalUrl = 'webcal://' + clubCalendarUrl;
    const httpsUrl = 'https://' + clubCalendarUrl;

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Content settings', 'myclub-sections' ) }>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Show subscribe button', 'myclub-sections' ) }
                            checked={ attributes.show_subscribe_button === '1' }
                            onChange={ ( value ) => {
                                setAttributes( { show_subscribe_button: value ? '1' : '0' } );
                            } }
                        />
                    </PanelRow>
                    <PanelRow>
                        <TextControl
                            label={ __( 'Calendar height', 'myclub-sections' ) }
                            value={ attributes.height }
                            placeholder={ calendarHeight || __( 'e.g. auto, 100%, 600', 'myclub-sections' ) }
                            help={ __( 'Override the default height. Leave empty to use the site setting.', 'myclub-sections' ) }
                            onChange={ ( value ) => {
                                setAttributes( { height: value } );
                            } }
                        />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <div {...useBlockProps()}>
                <div className="myclub-sections-club-calendar">
                    <div className="myclub-sections-club-calendar-container">
                        <h3 className="myclub-sections-header">{calendarTitle}</h3>
                        {optionsLoaded ? (
                            <div id="club-calendar-div" ref={ calendarElRef } />
                        ) : (
                            <p>{__('Loading...', 'myclub-sections')}</p>
                        )}
                    { attributes.show_subscribe_button === '1' && clubCalendarUrl && (
                        <>
                            <div className="myclub-sections-subscribe-button-wrapper">
                                <button
                                    className="myclub-sections-subscribe-button"
                                    onClick={ () => setSubscribeModalOpen( true ) }
                                >
                                    <Icon icon={ calendar } size={ 16 } />
                                    { __( 'Subscribe', 'myclub-sections' ) }
                                </button>
                            </div>
                            <div className={ 'club-calendar-modal' + ( subscribeModalOpen ? ' modal-open' : '' ) }>
                                <div className="modal-content subscribe-modal-content">
                                    <span className="close" onClick={ () => setSubscribeModalOpen( false ) }>&times;</span>
                                    <div className="modal-body subscribe-modal-body">
                                        <h3 className="subscribe-modal-title">{ __( 'Subscribe', 'myclub-sections' ) }</h3>
                                        <div className="subscribe-platform">
                                            <div className="subscribe-platform-header">
                                                <strong>{ __( 'iPhone, iPad, Mac', 'myclub-sections' ) }</strong>
                                            </div>
                                            <ol>
                                                <li>{ __( 'Use the browser on the respective device', 'myclub-sections' ) }</li>
                                                <li>{ __( 'Click the following link:', 'myclub-sections' ) } <a href={ webcalUrl }>{ webcalUrl }</a></li>
                                                <li>{ __( 'Click "Subscribe"', 'myclub-sections' ) }</li>
                                            </ol>
                                        </div>
                                        <div className="subscribe-platform">
                                            <div className="subscribe-platform-header">
                                                <strong>{ __( 'Android', 'myclub-sections' ) }</strong>
                                            </div>
                                            <p>{ __( 'Every Android device is associated with a Google account. Subscriptions in Android are done via Google, see below.', 'myclub-sections' ) }</p>
                                        </div>
                                        <div className="subscribe-platform">
                                            <div className="subscribe-platform-header">
                                                <strong>{ __( 'Google', 'myclub-sections' ) }</strong>
                                            </div>
                                            <ol>
                                                <li>{ __( 'Go to', 'myclub-sections' ) } <a href="https://www.google.com/calendar" target="_blank" rel="noopener">www.google.com/calendar</a> { __( '(requires a Google account)', 'myclub-sections' ) }</li>
                                                <li>{ __( 'Click the arrow to the right of "Other calendars" and then "Add web address"', 'myclub-sections' ) }</li>
                                                <li>{ __( 'Enter the URL:', 'myclub-sections' ) } <a href={ httpsUrl } target="_blank" rel="noopener">{ httpsUrl }</a> { __( 'and click "Add calendar"', 'myclub-sections' ) }</li>
                                            </ol>
                                        </div>
                                        <div className="subscribe-platform">
                                            <div className="subscribe-platform-header">
                                                <strong>{ __( 'Microsoft Outlook', 'myclub-sections' ) }</strong>
                                            </div>
                                            <ol>
                                                <li>{ __( 'Click the following link:', 'myclub-sections' ) } <a href={ webcalUrl }>{ webcalUrl }</a></li>
                                                <li>{ __( 'Open the link with Outlook', 'myclub-sections' ) }</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </>
                    ) }
                    <div className="club-calendar-modal" ref={modalRef}>
                        <div className="modal-content">
                            <span className="close">&times;</span>
                            <div className="modal-body">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </>
    );
}
