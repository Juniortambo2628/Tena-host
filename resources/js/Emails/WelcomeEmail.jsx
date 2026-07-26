import {
    Body,
    Container,
    Head,
    Heading,
    Hr,
    Html,
    Img,
    Link,
    Preview,
    Section,
    Text,
} from '@react-email/components';
import * as React from 'react';

export const WelcomeEmail = ({
    name = "Empire Builder",
    primaryColor = "#000000",
    accentColor = "#FFD300",
    businessName = "Tena",
    businessAddress = "Nairobi, Kenya",
    logoUrl = "/legacy/assets/Tena-logo-square.jpg",
    heading,
    body
}) => {
    const buttonStyle = {
        ...button,
        backgroundColor: primaryColor,
        color: accentColor,
    };

    return (
        <Html>
            <Head />
            <Preview>Welcome to {businessName} - Your dashboard for organized hosting.</Preview>
            <Body style={main}>
                <Container style={container}>
                    <Section style={header}>
                        <Img
                            src={logoUrl}
                            width="42"
                            height="42"
                            alt={businessName}
                            style={logo}
                        />
                    </Section>
                    <Section style={content}>
                        <Heading style={h1}>{heading || `Welcome home, ${name}.`}</Heading>
                        <Text style={text}>
                            {body || `${businessName} helps hosts build organized and well-coded dashboards full of beautiful and rich modules. We're excited to have you join our community of elite hosts.`}
                        </Text>
                        <Section style={buttonContainer}>
                            <Link style={buttonStyle} href="https://tena.app/dashboard">
                                Get Started
                            </Link>
                        </Section>
                        <Text style={text}>
                            Go ahead and start exploring your new dashboard. If you have any questions, our support team is always here to help.
                        </Text>
                    </Section>
                    <Hr style={hr} />
                    <Section style={footer}>
                        <Text style={footerText}>
                            © 2026 {businessName}. All rights reserved.
                        </Text>
                        <Text style={footerText}>{businessAddress}</Text>
                        <Link href="https://tena.app/terms" style={footerLink}>
                            Terms of Service
                        </Link>
                        <Link href="https://tena.app/privacy" style={footerLink}>
                            Privacy Policy
                        </Link>
                    </Section>
                </Container>
            </Body>
        </Html>
    );
};

export default WelcomeEmail;

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
    textTransform: 'tight',
};

const text = {
    color: '#444444',
    fontSize: '16px',
    lineHeight: '24px',
    margin: '20px 0',
};

const buttonContainer = {
    margin: '32px 0 40px',
};

const button = {
    backgroundColor: '#000000',
    borderRadius: '12px',
    color: '#FFD300',
    fontSize: '14px',
    fontWeight: '900',
    padding: '16px 32px',
    textDecoration: 'none',
    textTransform: 'uppercase',
    letterSpacing: '0.05em',
};

const hr = {
    borderColor: '#eeeeee',
    margin: '40px 0',
};

const footer = {
    padding: '0 20px',
};

const footerText = {
    color: '#888888',
    fontSize: '12px',
    lineHeight: '16px',
    margin: '4px 0',
};

const footerLink = {
    color: '#888888',
    fontSize: '12px',
    marginRight: '12px',
    textDecoration: 'underline',
};
