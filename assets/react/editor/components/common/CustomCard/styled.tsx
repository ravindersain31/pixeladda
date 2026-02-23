import React from 'react';
import styled from 'styled-components';

export const CardWrapper = styled.div`
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #f7f9fc;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
`;

export const CardHeader = styled.div`
  font-weight: 500;
  font-size: 14px;
  color: rgb(108, 136, 149);
  padding: 8px 0;
  background: rgb(239, 242, 248);
  border-bottom: 1px solid #ddd;
  border-radius: 8px 8px 0 0;
`;

export const CardBody = styled.div`
  padding: 12px;
  display: flex;
  justify-content: space-evenly;
  align-items: center;
`;