import { useEffect, useState } from "react";
import Search from "./components/Search";
import axios from "axios";

const CustomSearch = (props: any) => {
  const [searchConfig, setSearchConfig] = useState<any>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  const getSearchConfig = async () => {
    const { data } = await axios.get("/api/search-config/" + "CUSTOM");
    setSearchConfig(data);
    setIsLoading(false);
  };

  useEffect(() => {
    getSearchConfig();
  }, []);


  return (
    <>
      <Search searchConfig={searchConfig} isLoading={isLoading} />
    </>
  );
};
export default CustomSearch;
