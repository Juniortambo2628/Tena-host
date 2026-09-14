import {
    Body, Container, Head, Heading, Html, Img, Preview, Section, Text,
} from '@react-email/components';
import * as React from 'react';

export const ContactEnquiryEmail = ({
    senderName = 'Alex Superhost',
    senderEmail = 'alex@example.com',
    subjectLine = 'Interested in Tena for my listings',
    messageBody = "Hi team, I'd love to know more about the founding host program and pricing. Thanks!",
    primaryColor = '#000000',
    accentColor = '#FFD300',
    businessName = 'Tena',
    logoUrl = '/legacy/assets/Tena-logo-square.jpg',
    heading,
    body,
}) => {
    return (
        <Html>
            <Head />
            <Preview>New enquiry from {senderName} — {subjectLine}</Preview>
            <Body style={main}>
                <Container style={container}>
                    <Section style={header}>
                        <Img src={logoUrl} width="42" height="42" alt={businessName} style={logo} />
                    </Section>
                    <Section style={content}>
                        <Heading style={h1}>{heading || 'New contact enquiry'}</Heading>
                        <Text style={text}>
                            A visitor just sent a message from the {businessName} website.
                        </Text>
                        <Section style={metaBox}>
                            <Text style={metaRow}><strong>From:</strong> {senderName}</Text>
                            <Text style={metaRow}><strong>Email:</strong> {senderEmail}</Text>
                            <Text style={metaRow}><strong>Subject:</strong> {subjectLine}</Text>
                        </Section>
                        {body ? (
                            <Section style={text} dangerouslySetInnerHTML={{ __html: body }} />
                        ) : (
                            <Section style={messageBox}>
                                <Text style={messageLabel}>MESSAGE</Text>
                                <Text style={messageText}>{messageBody}</Text>
                            </Section>
                        )}
                    </Section>
                </Container>
            </Body>
        </Html>
    );
};

export default ContactEnquiryEmail;

const main = {
    backgroundColor: '#ffffff',
    fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif',
};
const container = { margin: '0 auto', padding: '20px 0 48px', width: '580px' };
const header = { padding: '32px 0' };
const logo = { borderRadius: '12px' };
const content = { padding: '0 20px' };
const h1 = { color: '#000000', fontSize: '24px', fontWeight: '900', lineHeight: '1.2', margin: '20px 0 12px' };
const text = { color: '#444444', fontSize: '15px', lineHeight: '1.7', margin: '12px 0' };
const metaBox = { background: '#f8f8f8', borderRadius: '12px', padding: '12px 20px', margin: '12px 0 20px' };
const metaRow = { color: '#333', fontSize: '14px', margin: '4px 0' };
const messageBox = { padding: '16px 20px', background: '#fafafa', borderLeft: '3px solid #FFD300', borderRadius: '8px', margin: '8px 0 16px' };
const messageLabel = { color: '#888', fontSize: '11px', letterSpacing: '0.08em', margin: '0 0 6px' };
const messageText = { color: '#333', fontSize: '15px', lineHeight: '1.7', margin: 0, whiteSpace: 'pre-wrap' };
