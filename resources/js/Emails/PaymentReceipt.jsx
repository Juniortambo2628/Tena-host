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

export const PaymentReceipt = ({
    customerName = "Valued Host",
    amount = "6,500",
    planName = "Pro Host Plan",
    transactionId = "TXN_12345678",
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

    const receiptValueStyle = {
        ...receiptValue,
        color: primaryColor,
    };

    return (
        <Html>
            <Head />
            <Preview>Your {businessName} Payment Receipt - {planName}</Preview>
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
                        <Heading style={h1}>{heading || "Payment Received."}</Heading>
                        {body ? (
                            <Section
                                style={text}
                                dangerouslySetInnerHTML={{ __html: body }}
                            />
                        ) : (
                            <Text style={text}>
                                {`Hello ${customerName}, we've successfully processed your payment for the ${planName}.`}
                            </Text>
                        )}
                        <Section style={receiptBox}>
                            <Section style={receiptLine}>
                                <Text style={receiptLabel}>Amount Paid</Text>
                                <Text style={receiptValueStyle}>KES {amount}</Text>
                            </Section>
                            <Section style={receiptLine}>
                                <Text style={receiptLabel}>Transaction ID</Text>
                                <Text style={receiptValueStyle}>{transactionId}</Text>
                            </Section>
                            <Section style={receiptLine}>
                                <Text style={receiptLabel}>Date</Text>
                                <Text style={receiptValueStyle}>{new Date().toLocaleDateString()}</Text>
                            </Section>
                        </Section>
                        <Text style={text}>
                            Your subscription is now active! You can access all pro modules and dashboard features immediately.
                        </Text>
                        <Section style={buttonContainer}>
                            <Link style={buttonStyle} href="https://tena.app/dashboard">
                                Go to Dashboard
                            </Link>
                        </Section>
                    </Section>
                    <Hr style={hr} />
                    <Section style={footer}>
                        <Text style={footerText}>
                            {businessName} Billing Team • {businessAddress}
                        </Text>
                        <Text style={footerText}>
                            If you didn't make this payment, please contact support@tena.app.
                        </Text>
                    </Section>
                </Container>
            </Body>
        </Html>
    );
};

export default PaymentReceipt;

const main = {
    backgroundColor: '#fafafa',
    fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif',
};

const container = {
    backgroundColor: '#ffffff',
    margin: '40px auto',
    padding: '40px',
    width: '560px',
    borderRadius: '24px',
    border: '1px solid #eeeeee',
};

const header = {
    marginBottom: '32px',
};

const logo = {
    borderRadius: '12px',
};

const content = {
    padding: '0',
};

const h1 = {
    color: '#000000',
    fontSize: '28px',
    fontWeight: '900',
    lineHeight: '1.2',
    margin: '0 0 24px',
};

const text = {
    color: '#444444',
    fontSize: '16px',
    lineHeight: '24px',
    margin: '16px 0',
};

const receiptBox = {
    backgroundColor: '#f9f9f9',
    borderRadius: '16px',
    padding: '24px',
    margin: '32px 0',
};

const receiptLine = {
    display: 'flex',
    justifyContent: 'space-between',
    marginBottom: '8px',
};

const receiptLabel = {
    color: '#888888',
    fontSize: '12px',
    fontWeight: '900',
    textTransform: 'uppercase',
    letterSpacing: '0.05em',
    margin: '0',
};

const receiptValue = {
    color: '#000000',
    fontSize: '14px',
    fontWeight: '900',
    margin: '0',
};

const buttonContainer = {
    margin: '32px 0 0',
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
    textAlign: 'center',
};

const hr = {
    borderColor: '#eeeeee',
    margin: '40px 0',
};

const footer = {
    padding: '0',
};

const footerText = {
    color: '#aaaaaa',
    fontSize: '12px',
    lineHeight: '18px',
    margin: '4px 0',
};
