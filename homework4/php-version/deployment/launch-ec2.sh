#!/bin/bash

# Launch Free Tier Eligible EC2 Instance for PHP TODO API
# This uses t3.micro (Free Tier eligible) with Ubuntu 22.04

set -e

echo "=== Launching EC2 Instance (Free Tier) ==="

# Configuration
INSTANCE_TYPE="t3.micro"  # Free Tier eligible
KEY_NAME="php-todo-api-key"
SECURITY_GROUP_NAME="php-todo-api-sg"
REGION=$(aws configure get region)

echo "Region: $REGION"

# Create key pair if it doesn't exist
if ! aws ec2 describe-key-pairs --key-names "$KEY_NAME" &> /dev/null; then
    echo "Creating new key pair: $KEY_NAME"
    aws ec2 create-key-pair --key-name "$KEY_NAME" --query 'KeyMaterial' --output text > "${KEY_NAME}.pem"
    chmod 400 "${KEY_NAME}.pem"
    echo "Key pair saved to ${KEY_NAME}.pem"
else
    echo "Key pair $KEY_NAME already exists"
fi

# Get default VPC ID
VPC_ID=$(aws ec2 describe-vpcs --filters "Name=isDefault,Values=true" --query 'Vpcs[0].VpcId' --output text)
echo "Using VPC: $VPC_ID"

# Create security group if it doesn't exist
if ! aws ec2 describe-security-groups --group-names "$SECURITY_GROUP_NAME" &> /dev/null; then
    echo "Creating security group: $SECURITY_GROUP_NAME"
    SG_ID=$(aws ec2 create-security-group \
        --group-name "$SECURITY_GROUP_NAME" \
        --description "Security group for PHP TODO API" \
        --vpc-id "$VPC_ID" \
        --query 'GroupId' \
        --output text)

    echo "Security Group ID: $SG_ID"

    # Add inbound rules
    echo "Adding security group rules..."

    # SSH (port 22)
    aws ec2 authorize-security-group-ingress \
        --group-id "$SG_ID" \
        --protocol tcp \
        --port 22 \
        --cidr 0.0.0.0/0

    # HTTP (port 80)
    aws ec2 authorize-security-group-ingress \
        --group-id "$SG_ID" \
        --protocol tcp \
        --port 80 \
        --cidr 0.0.0.0/0

    # HTTPS (port 443) - for future SSL
    aws ec2 authorize-security-group-ingress \
        --group-id "$SG_ID" \
        --protocol tcp \
        --port 443 \
        --cidr 0.0.0.0/0

    echo "Security group rules added"
else
    echo "Security group $SECURITY_GROUP_NAME already exists"
    SG_ID=$(aws ec2 describe-security-groups --group-names "$SECURITY_GROUP_NAME" --query 'SecurityGroups[0].GroupId' --output text)
fi

# Get latest Ubuntu 22.04 AMI (Free Tier eligible)
echo "Finding latest Ubuntu 22.04 AMI..."
AMI_ID=$(aws ec2 describe-images \
    --owners 099720109477 \
    --filters "Name=name,Values=ubuntu/images/hvm-ssd/ubuntu-jammy-22.04-amd64-server-*" \
    --query 'Images | sort_by(@, &CreationDate) | [-1].ImageId' \
    --output text)

echo "Using AMI: $AMI_ID"

# Launch EC2 instance
echo "Launching EC2 instance..."
INSTANCE_ID=$(aws ec2 run-instances \
    --image-id "$AMI_ID" \
    --instance-type "$INSTANCE_TYPE" \
    --key-name "$KEY_NAME" \
    --security-group-ids "$SG_ID" \
    --tag-specifications "ResourceType=instance,Tags=[{Key=Name,Value=php-todo-api}]" \
    --query 'Instances[0].InstanceId' \
    --output text)

echo "Instance ID: $INSTANCE_ID"
echo "Waiting for instance to be running..."

aws ec2 wait instance-running --instance-ids "$INSTANCE_ID"

# Get public IP
PUBLIC_IP=$(aws ec2 describe-instances \
    --instance-ids "$INSTANCE_ID" \
    --query 'Reservations[0].Instances[0].PublicIpAddress' \
    --output text)

echo ""
echo "=== EC2 Instance Launched Successfully! ==="
echo "Instance ID: $INSTANCE_ID"
echo "Public IP: $PUBLIC_IP"
echo "Key File: ${KEY_NAME}.pem"
echo ""
echo "To connect to your instance, run:"
echo "  ssh -i ${KEY_NAME}.pem ubuntu@${PUBLIC_IP}"
echo ""
echo "After connecting, upload and run the deploy-ec2-setup.sh script:"
echo "  scp -i ${KEY_NAME}.pem deploy-ec2-setup.sh ubuntu@${PUBLIC_IP}:~/"
echo "  ssh -i ${KEY_NAME}.pem ubuntu@${PUBLIC_IP}"
echo "  chmod +x deploy-ec2-setup.sh"
echo "  ./deploy-ec2-setup.sh"
echo ""
