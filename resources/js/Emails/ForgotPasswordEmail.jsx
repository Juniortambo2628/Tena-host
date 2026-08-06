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

export const ForgotPasswordEmail = ({
    userName = "Empire Builder",
    resetLink = "https://tena.app/password/reset",
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
            <Preview>Reset your {businessName} password</Preview>
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
                        <Heading style={h1}>{heading || "Reset Request."}</Heading>
                        {body ? (
                            <Section
                                style={text}
                                dangerouslySetInnerHTML={{ __html: body }}
                            />
                        ) : (
                            <Text style={text}>
                                {`Hello ${userName}, we received a request to reset your password for your ${businessName} account. If this was you, please click the button below to set a new password.`}
                            </Text>
                        )}
                        <Section style={buttonContainer}>
                            <Link style={buttonStyle} href={resetLink}>
                                Reset Password
                            </Link>
                        </Section>
                        <Text style={text}>
                            If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.
                        </Text>
                        <Text style={subtext}>
                            Link expires in 60 minutes.
                        </Text>
                    </Section>
                    <Hr style={hr} />
                    <Section style={footer}>
                        <Text style={footerText}>
                            {businessName} Security Team • {businessAddress}
                        </Text>
                    </Section>
                </Container>
            </Body>
        </Html>
    );
};

export default ForgotPasswordEmail;

const main = {
    backgroundColor: '#ffffff',
    fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif',
};

const container = {
    margin: '0 auto',
    padding: '40px 0',
    width: '580px',
};

const header = {
    padding: '0 20px',
};

const logo = {
    borderRadius: '12px',
};

const content = {
    padding: '0 20px',
};

const h1 = {
    color: '#000000',
    fontSize: '28px',
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

const subtext = {
    color: '#888888',
    fontSize: '12px',
    margin: '24px 0 0',
};

const buttonContainer = {
    margin: '32px 0 40px',
};

const button = {
    backgroundColor: '#000000',
    borderRadius: '12px',
    color: '#FFD300',
    display: 'inline-block',
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
    color: '#aaaaaa',
    fontSize: '12px',
    lineHeight: '16px',
    margin: '4px 0',
};
