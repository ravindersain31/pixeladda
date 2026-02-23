import { useState, useEffect, useRef } from "react";
import { Input, Image, message, Space } from "antd";
import { handleGoogleImageUpload } from "./handleGoogleImageUpload";
import {
  GoogleImage,
  StyledSpace,
  StyledButton,
  GoogleImageInput,
  FileItem,
  FileItemDiv,
  ScrollContainer,
} from "./styled";

interface GoogleImageSearchProps {
  uploadUrl: string;
  fileList: any[];
  onFileListChange: (files: any[]) => void;
  onClose: () => void;
  setUploading: (val: boolean) => void;
}

export interface GoogleImageResult {
  link: string;
  title: string;
}
declare global {
  interface Window {
    googleApiKey: string;
    googleSearchEngineId: string;
  }
}

const GOOGLE_API_KEY = window.googleApiKey;
const GOOGLE_CX = window.googleSearchEngineId;

const GoogleImageSearch = ({
  uploadUrl,
  fileList,
  onFileListChange,
  onClose,
  setUploading,
}: GoogleImageSearchProps) => {
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<GoogleImageResult[]>([]);
  const [loading, setLoading] = useState(false);
  const [startIndex, setStartIndex] = useState(1);
  const [hasMore, setHasMore] = useState(true);

  const scrollRef = useRef<HTMLDivElement>(null);

  const search = async (isLoadMore = false) => {
    if (!query) return;

    setLoading(true);
    const fetchWithRetry = async (url: string, retries = 3, delay = 1000) => {
      for (let i = 0; i < retries; i++) {
        const res = await fetch(url);
        if (res.status === 429 && i < retries - 1) {
          await new Promise((r) => setTimeout(r, delay * (i + 1)));
        } else {
          return res;
        }
      }
      throw new Error("Google API rate limit hit too many times.");
    };

    try {
      const res = await fetchWithRetry(
        `https://www.googleapis.com/customsearch/v1?key=${GOOGLE_API_KEY}&cx=${GOOGLE_CX}&q=${encodeURIComponent(
          query
        )}&searchType=image&num=10&start=${startIndex}`
      );
      const data = await res.json();
      if (data.error) {
        console.error("Google API Error:", data.error);
        message.error(data.error.message || "Failed to fetch Google images.");
        setLoading(false);
        return;
      }
      const filteredItems = (data.items || []).filter(
        (item: GoogleImageResult) => {
          const url = item.link || "";
          return (
            url &&
            !url.includes("instagram.com") &&
            !url.includes("lookaside.instagram.com") &&
            !url.includes("media") &&
            !url.includes("redd.it")
          );
        }
      );

      if (filteredItems.length > 0) {
        setResults((prev) =>
          isLoadMore ? [...prev, ...filteredItems] : filteredItems
        );
        setStartIndex((prev) => prev + 4);
        setHasMore(Boolean(data.queries?.nextPage));
      } else {
        setHasMore(false);
      }
    } catch (err) {
      message.error("Failed to fetch Google images.");
    } finally {
      setLoading(false);
    }
  };

  const handleSelectImage = (item: GoogleImageResult) => {
    handleGoogleImageUpload({
      image: item,
      fileList,
      onFileListChange,
      uploadUrl,
      onClose,
      setUploading,
    });
    setQuery("");
    setResults([]);
    setStartIndex(1);
    setHasMore(true);
  };

  useEffect(() => {
    const container = scrollRef.current;
    if (!container) return;

    const handleScroll = () => {
      if (loading || !hasMore) return;

      const { scrollTop, scrollHeight, clientHeight } = container;
      if (scrollTop + clientHeight >= scrollHeight - 20) {
        search(true);
      }
    };

    container.addEventListener("scroll", handleScroll);
    return () => container.removeEventListener("scroll", handleScroll);
  }, [loading, hasMore, startIndex, query]);

  const handleInitialSearch = () => {
    if (!query.trim()) {
      message.warning("Please enter a search term.");
      return;
    }
    setStartIndex(1);
    setResults([]);
    setHasMore(true);
    if (scrollRef.current) {
      scrollRef.current.scrollTop = 0;
    }
    search();
  };

  return (
    <GoogleImage className="ysp-google-image-main">
      <StyledSpace className="ysp-google-image">
        <GoogleImageInput
          className="ysp-google-image-input"
          value={query}
          onChange={(e: any) => setQuery(e.target.value)}
          placeholder="Search Google Images"
        />
        <StyledButton
          className="btn-ysp-google-image-upload"
          onClick={handleInitialSearch}
          loading={loading}
        >
          Search
        </StyledButton>
      </StyledSpace>
      {results.length > 0 && (
        <ScrollContainer className="ysp-google-image-scroll" ref={scrollRef}>
          <FileItemDiv className="ysp-google-image-results">
            {results.map((item, index) => (
              <FileItem key={`${item.link}-${index}`}>
                <Image
                  src={item.link}
                  width={140}
                  height={140}
                  style={{ borderRadius: 8, objectFit: "cover" }}
                  preview={false}
                />
                <StyledButton
                  className="handle-select-image"
                  onClick={() => handleSelectImage(item)}
                >
                  Upload Image
                </StyledButton>
              </FileItem>
            ))}
          </FileItemDiv>
        </ScrollContainer>
      )}
    </GoogleImage>
  );
};

export default GoogleImageSearch;
