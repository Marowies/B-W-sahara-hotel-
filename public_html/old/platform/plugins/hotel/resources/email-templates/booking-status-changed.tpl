{{ header }}

<table width="100%">
    <tbody>
        <tr>
            <td class="wrapper" width="700" align="center">
                <table class="section" cellpadding="0" cellspacing="0" width="700" bgcolor="#f8f8f8">
                    <tr>
                        <td class="column" align="left">
                            <table>
                                <tbody>
                                <tr>
                                    <td align="left" style="padding: 20px 50px;">
                                        <p>
                                            <strong>{{ 'plugins/hotel::hotel.email_templates.booking_status_changed.greeting' | trans({'site_title': site_title}) }}</strong>
                                        </p>
                                        <p>
                                            {{ 'plugins/hotel::hotel.email_templates.booking_status_changed.message' | trans }}
                                        </p>
                                        <p>
                                            <strong>{{ 'plugins/hotel::hotel.email_templates.booking_status_changed.booking_details_title' | trans }}</strong>
                                            <ul>
                                                <li><strong>{{ 'plugins/hotel::hotel.email_templates.booking_status_changed.field_name' | trans }}:</strong> {{ booking_name }}</li>
                                                <li><strong>{{ 'plugins/hotel::hotel.email_templates.booking_status_changed.field_date_of_booking' | trans }}:</strong> {{ booking_date }}</li>
                                                <li><strong>{{ 'plugins/hotel::hotel.email_templates.booking_status_changed.view_booking_detail' | trans }}:</strong> {{ booking_link }}</li>
                                            </ul>
                                        </p>
                                        <p>
                                            <strong>{{ 'plugins/hotel::hotel.email_templates.booking_status_changed.new_booking_status' | trans }}:</strong> {{ booking_status }}
                                        </p>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </tbody>
</table>

{{ footer }}
