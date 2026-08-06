import {
    Body, Container, Head, Heading, Html, Img, Section, Text,
} from '@react-email/components';
import * as React from 'react';

export const WaitlistWelcomeEmail = ({
    firstName = "Test",
    lastName = "User",
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
            <Preview>Welcome to the {businessName} Family!</Preview>
            <Body style={main}>
                <Container style={container}>
                    <Section style={header}>
                        <Img src={logoUrl} width="42" height="42" alt={businessName} style={logo} />
                    </Section>
                    <Section style={content}>
                        <Heading style={h1}>{heading || 'Welcome to the Tena Family!'}</Heading>
                        {body ? (
                            <Section style={text} dangerouslySetInnerHTML={{ __html: body }} />
                        ) : (
                            <Text style={text}>
                                Hey {firstName}, we're excited to share that {businessName} is getting closer to launch! As a waitlist member, you'll be among the first to access our platform.
                            </Text>
                        )}
                        <Text style={text}>
                            Got questions? Just reply to this email — we'd love to hear from you.
                        </Text>
                    </Section>
                </Container>
            </Body>
        </Html>
    );
};

export default WaitlistWelcomeEmail;

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
