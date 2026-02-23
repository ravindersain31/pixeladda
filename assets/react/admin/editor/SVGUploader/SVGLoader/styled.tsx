import styled from 'styled-components';
import { Input } from 'antd';

export const SVGContainer = styled.div`
  label {
    margin-bottom: 10px;
  }
`;

export const SVGLoaderForm = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
`;

export const SVGLoaderInput = styled(Input)`
`;

export const SavingDesign = styled.div`
  margin-top: 5px;
  font-size: small;
`;

export const MessageBox = styled.div<{ type?: 'info' | 'success' | 'error' }>`
  margin-top: 5px;
  font-size: small;
  color: ${({ type }) =>
    type === 'success' ? '#22863a' :
    type === 'error' ? '#e81500' :
    type === 'info' ? '#0366d6' : '#69707a'};
`;