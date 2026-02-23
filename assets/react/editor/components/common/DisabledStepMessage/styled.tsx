import styled, { keyframes }  from "styled-components";


const fadeIn = keyframes`
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
`;

export const DisableStepWrapper = styled.div`
    text-align: center;
    color: #333;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 100;
    background: rgba(169, 169, 169, 0.9);
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: not-allowed;
    padding: 20px 30px;
    font-weight: 600;
    font-size: 18px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    animation: ${fadeIn} 0.3s ease-in;
`;