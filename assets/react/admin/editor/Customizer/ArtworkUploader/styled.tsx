import styled from 'styled-components';
import { Input } from 'antd';
import Button from "@react/admin/editor/components/Button";

export const ArtworkContainer = styled.div`
  background: #f2f2f2;
  padding: 15px 15px 20px;
  margin-bottom: 10px;
  border-radius: 10px;

  label {
    margin-bottom: 10px;
  }
`;

export const ArtworkLoaderForm = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
`;

export const ArtworkLoaderInput = styled(Input)`
  margin-right: 10px;
  height: 32px;
  padding: 5px;
  font-size: 10px;
`;

export const ArtworkLoadButton = styled(Button)`
`;