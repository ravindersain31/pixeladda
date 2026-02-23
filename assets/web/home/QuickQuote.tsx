import React, { useEffect, useState } from 'react';
import QuickQuoteModal from './components/QuickQuote';
import axios from "axios";

const QuickQuote = () => {
  const [data, setData] = useState<any>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  const getProduct = async (sku: string = 'CUSTOM-SIZE') => {
    try {
      const { data, status } = await axios.get(`/api/quick-quote/${sku}`);
      if (status === 200) {
        setData(data);
        setIsLoading(false);
      }
    } catch (error) {
      console.error("Error loading custom templates:", error);
    }
  };

  useEffect(() => {
    getProduct();
  }, []);

  return (
    <>
      <QuickQuoteModal data={data} isLoading={isLoading}/>
    </>
  );
};

export default QuickQuote;
