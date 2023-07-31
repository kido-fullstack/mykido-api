import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import sys
def send_email(sender_name, sender_email, receiver_email, subject, body, smtp_server, smtp_port, smtp_username, smtp_password):
    # Create a MIMEText object for the email body
    email_body = MIMEText(body, 'plain', 'utf-8')
    # Create a MIMEMultipart object for the email
    email_message = MIMEMultipart()
    email_message['From'] = sender_name+"<"+sender_email+">"
    email_message['To'] = "mykido@kido.school"
    email_message['Subject'] = subject

    email_message.attach(email_body)
    email_text = email_message.as_string()
    try:
        s = smtplib.SMTP('smtp.gmail.com', 587)
        s.ehlo()
        s.starttls()
        s.ehlo()
        s.login(smtp_username, smtp_password)
        for dest in receiver_email:
            s.sendmail(sender_email, dest, email_text)
        s.quit()
    except Exception as e:
        print("Failed to send email. Error:"+e)

# Replace these with your own values
sender_name = "Mykido Inspection"
sender_email = "enquiry@kidovillage.com"
receiver_email = ["ziauddin.sayyed@kido.school","anjali.motiani@kido.school"]
subject = "Inspection submitted by "+sys.argv[1]
body = sys.argv[2]
smtp_server = "smtp.gmail.com"
smtp_port = 587
smtp_username = "enquiry@kidovillage.com"
smtp_password = "AP@Kido_u6123#1"
# Send the email
send_email(sender_name, sender_email, receiver_email, subject, body, smtp_server, smtp_port, smtp_username, smtp_password)







