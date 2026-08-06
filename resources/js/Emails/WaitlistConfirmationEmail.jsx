import {
    Body, Container, Head, Heading, Html, Img, Preview, Section, Text,
} from '@react-email/components';
import * as React from 'react';

export const WaitlistConfirmationEmail = ({
    firstName = "Test",
    lastName = "User",
    email = "test@example.com",
    propertyType = "Vacation Rental",
    units = "5",
    primaryPlatform = "Airbnb",
    biggestChallenge = "Managing multiple platforms",
    primaryColor = "#000000",
    accentColor = "#FFD300",
    businessName = "Tena",
    businessAddress = "Nairobi, Kenya",
    logoUrl = "/legacy/assets/Tena-logo-square.jpg",
    heading,
    body
}) => {
    return (
        <Html>
            <Head />
            <Preview>You're on the {businessName} waitlist!</Preview>
            <Body style={main}>
                <Container style={container}>
                    <Section style={header}>
                        <Img src={logoUrl} width="42" height="42" alt={businessName} style={logo} />
                    </Section>
                    <Section style={content}>
                        <Heading style={h1}>{heading || "You're on the list!"}</Heading>
                        {body ? (
                            <Section style={text} dangerouslySetInnerHTML={{ __html: body }} />
                        ) : (
                            <Text style={text}>
                                Hi {firstName}, thanks for joining the {businessName} waitlist! We're thrilled to have you on board.
                            </Text>
                        )}
                        <Section style={tableWrap}>
                            <table width="100%" cellPadding="0" cellSpacing="0" style={table}>
                                <tr>
                                    <td style={tableLabel}>Property Type</td>
                                    <td style={tableValue}>{propertyType}</td>
                                </tr>
                                <tr>
                                    <td style={tableLabel}>Units</td>
                                    <td style={tableValue}>{units}</td>
                                </tr>
                                <tr>
                                    <td style={tableLabel}>Primary Platform</td>
                                    <td style={tableValue}>{primaryPlatform}</td>
                                </tr>
                                <tr>
                                    <td style={tableLabel}>Biggest Challenge</td>
                                    <td style={tableValue}>{biggestChallenge}</td>
                                </tr>
                            </table>
                        </Section>
                        <Text style={text}>
                            We're building something special for hosts like you, and your input matters. We'll keep you updated on our progress.
                        </Text>
                    </Section>
                </Container>
            </Body>
        </Html>
    );
};

export default WaitlistConfirmationEmail;

const main = {
    backgroundColor: '#ffffff',
    fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif',
};

const container = {
    margin: '0 auto',
    padding: '20px 0 48px',
    width: '580px',
};

const header = {
    padding: '32px 0',
};

const logo = {
    borderRadius: '12px',
};

const content = {
    padding: '0 20px',
};

const h1 = {
    color: '#000000',
    fontSize: '24px',
    fontWeight: '900',
    lineHeight: '1.2',
    margin: '40px 0 20px',
};

const text = {
    color: '#444444',
    fontSize: '16px',
    lineHeight: '24px',
    margin: '20px 0',
};

const tableWrap = {
    margin: '24px 0',
};

const table = {
    backgroundColor: '#f8f8f8',
    borderRadius: '12px',
};

const tableLabel = {
    padding: '12px 20px',
    fontSize: '13px',
    color: '#888',
    borderBottom: '1px solid #eee',
};

const tableValue = {
    padding: '12px 20px',
    fontSize: '14px',
    fontWeight: '600',
    color: '#333',
    borderBottom: '1px solid #eee',
};
